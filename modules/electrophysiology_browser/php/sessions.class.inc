<?php declare(strict_types=1);
/**
 * This class features the code for the menu portion of the LORIS
 * electrophysiology browser module.
 *
 * PHP version 7
 *
 * @category   Behavioural
 * @package    Main
 * @subpackage Electrophysiology
 * @author     Loris team <info-loris.mni@mcgill.ca>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @link       https://github.com/aces/Loris
 */
namespace LORIS\electrophysiology_browser;

use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use LORIS\electrophysiology_browser\Models\ElectrophysioFile;
use SodiumException;

/**
 * Provides the PHP code for the menu filter for the electrophysiology browser
 *
 * @category   Behavioural
 * @package    Main
 * @subpackage Sessions
 * @author     Loris team <info-loris.mni@mcgill.ca>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @link       https://github.com/aces/Loris
 */

class Sessions extends \NDB_Page
{

    public $skipTemplate = true; // stops from looking for a smarty template
    protected $timepoint;
    protected $sessionID;
    protected $candidate;

    /**
     * Determine whether the user has permission to view this page
     *
     * @param \User $user The user whose access is being checked
     *
     * @return bool whether the user has access
     */
    function _hasAccess(\User $user) : bool
    {
        return (($user->hasPermission('electrophysiology_browser_view_allsites')
                || ($user->hasCenter($this->timepoint->getCenterID())
                && $user->hasPermission('electrophysiology_browser_view_site'))
            ) && $user->hasProject($this->timepoint->getProject()->getId()));
    }

    /**
     * Load the required variables in order to check that the  user
     * has access to the session.
     *
     * @param \User                  $user    The user to load the resources for
     * @param ServerRequestInterface $request The PSR15 Request being handled
     *
     * @return void
     * @throws \LorisException  If the session is not found
     * @throws \NotFound If the session id is non-numerical
     */
    public function loadResources(
        \User $user, ServerRequestInterface $request
    ) : void {

        $path    = $request->getUri()->getPath();
        $matches = [];

        if (preg_match('#/sessions/(\d+)#', $path, $matches) !== 1) {
            throw new \NotFound("Invalid session");
        }
        $session_id = intval($matches[1]);

        try {
            $this->timepoint = \NDB_Factory::singleton()->timepoint(
                new \SessionID(strval($session_id))
            );
            $this->sessionID = $session_id;
        } catch(\LorisException $e) {
            throw new \NotFound("Session not found");
        }

        parent::loadResources($user, $request);
    }

    /**
     * Handles a login request
     *
     * @param ServerRequestInterface $request The incoming PSR7 request
     *
     * @return ResponseInterface The outgoing PSR7 response
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $user       = $request->getAttribute('user');

        if (!$this->_hasAccess($user)) {
            return (new \LORIS\Middleware\PageDecorationMiddleware($user))
                ->process(
                    $request,
                    new \LORIS\Http\StringStream("Permission denied")
                )->withStatus(403);
        }

        $accepted_hdrs = explode(
            ',',
            $request->getHeaderLine('Accept')
        );

        if (in_array('text/html', $accepted_hdrs, true)) {
            return parent::handle($request);
        }

        if (!isset($parameters['outputType'])) {
            return (new \LORIS\Http\Response\JSON\BadRequest(
                'outputType required'
            ));
        }

        $outputType = $parameters['outputType'];
        if (!in_array($outputType, ['raw', 'derivative', 'all_types'])) {
            return (new \LORIS\Http\Response\JSON\BadRequest(
                'invalid output type'
            ));
        }

        switch ($request->getMethod()) {
        case 'GET':

            if (array_key_exists('physioFileID', $parameters)
                && array_key_exists('filePath', $parameters)
            ) {
                $physioFileID = intval($parameters['physioFileID']) ?? null;
                error_log(print_r("physio ID: ".$physioFileID, true));
                error_log(print_r("path: ".$parameters['filePath'], true));

                self::updateDerivativeFiles($physioFileID);

                $config       = \NDB_Factory::singleton()->config();
                $downloadpath = \Utility::appendForwardSlash(
                    $config->getSetting("dataDirBasepath")
                );

                $file     = $parameters['filePath'] ?? null;
                $filename = urldecode(basename($file));
                $path     = dirname($file);

                $downloader = new \LORIS\FilesDownloadHandler(
                    new \SPLFileInfo($downloadpath . $path)
                );
                return $downloader->handle(
                    $request->withAttribute('filename', $filename)
                );
            } else {
                return $this->getSessionData($outputType);
            }

        case 'DELETE':
            $this->_deleteAnnotation($request);
        }

        return $this->getSessionData($outputType);
    }


    /**
     * Get the session data information.
     *
     * @param string $outputType output type to be displayed
     *
     * @return ResponseInterface The JSON response
     */
    function getSessionData($outputType)
    {
        $db = \NDB_Factory::singleton()->database();

        $query = 'SELECT
                DISTINCT(pf.SessionID)
              FROM physiological_file pf
                LEFT JOIN session s ON (s.ID=pf.SessionID)
                LEFT JOIN candidate c USING (CandID)
                LEFT JOIN psc ON (s.CenterID=psc.CenterID)
                LEFT JOIN physiological_output_type pot
                  USING (PhysiologicalOutputTypeID)
              WHERE
                s.Active = "Y"
                AND pf.FileType IN ("bdf", "cnt", "edf", "set", "vhdr", "vsm")
              ORDER BY pf.SessionID';

        $response = [];

        $sessions            = $db->pselect($query, []);
        $sessions            = array_column($sessions, 'SessionID');
        $response['patient'] = $this->getSubjectData($outputType);
        $response['database']    = array_values(
            $this->getFilesData($outputType)
        );
        $response['sessions']    = $sessions;
        $currentIndex            = array_search($this->sessionID, $sessions);
        $response['nextSession'] = $sessions[$currentIndex+1] ?? '';
        $response['prevSession'] = $sessions[$currentIndex-1] ?? '';

        return new \LORIS\Http\Response\JSON\OK($response);
    }


    /**
     * Get the subject data information.
     *
     * @param string $outputType output type to be displayed
     *
     * @return array with the subject information
     */
    function getSubjectData($outputType)
    {
        $subjectData = [];
        $candidate   = \NDB_Factory::singleton()->candidate(
            $this->timepoint->getCandID()
        );

        $subjectData['pscid']       = $candidate->getPSCID();
        $subjectData['dccid']       = $candidate->getCandID();
        $subjectData['visit_label'] = $this->timepoint->getVisitLabel();
        $subjectData['sessionID']   = $this->timepoint->getSessionID();
        $subjectData['site']        = $this->timepoint->getPSC();
        $subjectData['dob']         = $candidate->getCandidateDoB();
        $subjectData['sex']         = $candidate->getCandidateSex();
        $subjectData['subproject']  = $this->timepoint->getData('SubprojectTitle');
        $subjectData['output_type'] = $outputType;

        return $subjectData;
    }

    /**
     * Get the list of electrophysiology recordings with their recording information.
     *
     * @param string $outputType output type to be displayed
     *
     * @return array with the file collection
     */
    function getFilesData(string $outputType)
    {
        $db = \NDB_Factory::singleton()->database();

        $fileCollection = [];
        $params         = [];
        $params['SID']  = $this->sessionID;
        $query          = 'SELECT
                         pf.PhysiologicalFileID,
                         pf.FilePath
                       FROM
                         physiological_file pf ';

        if ($outputType != 'all_types') {
            $query        .= 'LEFT JOIN physiological_output_type pot ON ';
            $query        .= 'pf.PhysiologicalOutputTypeID='
                . 'pot.PhysiologicalOutputTypeID ';
            $query        .= 'WHERE SessionID=:SID ';
            $query        .= 'AND pot.OutputTypeName = :OTN ';
            $params['OTN'] = $outputType;
        } else {
            $query .= "WHERE SessionID=:SID";
        }

        $physiologicalFiles = $db->pselect($query, $params);

        foreach ($physiologicalFiles as $file) {
            $fileSummary         = [];
            $physiologicalFileID = $file['PhysiologicalFileID'];
            $physiologicalFile   = $file['FilePath'];
            $physioFileObj       = new ElectrophysioFile(
                intval($physiologicalFileID)
            );
            $fileName            = basename(
                $physioFileObj->getParameter('FilePath')
            );

            // -----------------------------------------------------
            // Create a file summary object with file's information
            // -----------------------------------------------------

            // get the file name

            $fileSummary['name'] = $fileName;

            // get the file ID

            $fileSummary['id'] = $physiologicalFileID;

            // get the task frequency information

            $sampling  = $physioFileObj->getParameter('SamplingFrequency');
            $powerline = $physioFileObj->getParameter('PowerLineFrequency');

            $fileSummary['task']['frequency']['sampling']  = $sampling;
            $fileSummary['task']['frequency']['powerline'] = $powerline;

            // get the task channel information

            $eegChannelCount = $physioFileObj->getParameter('EEGChannelCount');
            $eogChannelCount = $physioFileObj->getParameter('EOGChannelCount');
            $ecgChannelCount = $physioFileObj->getParameter('ECGChannelCount');
            $emgChannelCount = $physioFileObj->getParameter('EMGChannelCount');

            $fileSummary['task']['channel'][] = [
                'name'  => 'EEG Channel Count',
                'value' => $eegChannelCount,
            ];
            $fileSummary['task']['channel'][] = [
                'name'  => 'EOG Channel Count',
                'value' => $eogChannelCount,
            ];
            $fileSummary['task']['channel'][] = [
                'name'  => 'ECG Channel Count',
                'value' => $ecgChannelCount,
            ];
            $fileSummary['task']['channel'][] = [
                'name'  => 'EMG Channel Count',
                'value' => $emgChannelCount,
            ];

            // get the task reference

            $reference = $physioFileObj->getParameter('EEGReference');

            $fileSummary['task']['reference'] = $reference;

            // get the file's details

            $taskDesc         = $physioFileObj->getParameter('TaskDescription');
            $instructions     = $physioFileObj->getParameter('Instructions');
            $placement        = $physioFileObj->getParameter('EEGPlacementScheme');
            $triggerCount     = $physioFileObj->getParameter('TriggerChannelCount');
            $recordingType    = $physioFileObj->getParameter('Recording_type');
            $cogAtlasID       = $physioFileObj->getParameter('CogAtlasID');
            $cogPoid          = $physioFileObj->getParameter('CogPOID');
            $instituteName    = $physioFileObj->getParameter('InstitutionName');
            $intituteAddress  = $physioFileObj->getParameter('InstitutionAddress');
            $miscChannelCount = $physioFileObj->getParameter('MiscChannelCount');
            $manufacturer     = $physioFileObj->getParameter('Manufacturer');
            $modelName        = $physioFileObj->getParameter(
                'ManufacturerModelName'
            );
            $capManufacturer  = $physioFileObj->getParameter(
                'ManufacturerCapModelName'
            );
            $capModelName     = $physioFileObj->getParameter(
                'ManufacturerCapModelName'
            );
            $hardwareFilters  = $physioFileObj->getParameter('HardwareFilters');
            $duration         = $physioFileObj->getParameter('RecordingDuration');
            $epochLength      = $physioFileObj->getParameter('EpochLength');
            $softwareVersion  = $physioFileObj->getParameter(
                'DeviceSoftwareVersion'
            );
            $serialNumber     = $physioFileObj->getParameter('DeviceSerialNumber');
            $artefactDesc     = $physioFileObj->getParameter(
                'SubjectArtefactDescription'
            );

            $fileSummary['details']['task']['description']     = $taskDesc;
            $fileSummary['details']['instructions']            = $instructions;
            $fileSummary['details']['eeg']['ground']           = '';
            $fileSummary['details']['eeg']['placement_scheme'] = $placement;
            $fileSummary['details']['trigger_count']           = $triggerCount;
            $fileSummary['details']['record_type']            = $recordingType;
            $fileSummary['details']['cog']['atlas_id']        = $cogAtlasID;
            $fileSummary['details']['cog']['poid']            = $cogPoid;
            $fileSummary['details']['institution']['name']    = $instituteName;
            $fileSummary['details']['institution']['address'] = $intituteAddress;
            $fileSummary['details']['misc']['channel_count']  = $miscChannelCount;
            $fileSummary['details']['manufacturer']['name']   = $manufacturer;
            $fileSummary['details']['manufacturer']['model_name'] = $modelName;
            $fileSummary['details']['cap']['manufacturer']        = $capManufacturer;
            $fileSummary['details']['cap']['model_name']          = $capModelName;
            $fileSummary['details']['hardware_filters']           = $hardwareFilters;
            $fileSummary['details']['recording_duration']         = $duration;
            $fileSummary['details']['epoch_length']            = $epochLength;
            $fileSummary['details']['device']['version']       = $softwareVersion;
            $fileSummary['details']['device']['serial_number'] = $serialNumber;
            $fileSummary['details']['subject_artefact_description'] = $artefactDesc;

            // get the links to the files for downloads

            $links = $this->getDownloadLinks(
                intval($physiologicalFileID),
                $physiologicalFile
            );

            $fileSummary['downloads'] = $links;

            $fileOutput = $db->pselectone(
                'SELECT pot.OutputTypeName
                FROM physiological_output_type pot
                INNER JOIN physiological_file AS pf
                ON pf.PhysiologicalFileID=:PFID
                AND pf.PhysiologicalOutputTypeID=pot.PhysiologicalOutputTypeID',
                ['PFID' => $physiologicalFileID]
            );
            //Get the annotation data if the output type is derivative
            //if (in_array($outputType, ['derivatives', 'all_types'])) {
            if (strcmp($fileOutput, 'derivative') == 0) {
                $annotations = $this->getAnnotations(intval($physiologicalFileID));
                $fileSummary['annotations'] = $annotations;
            }

            $fileCollection[]['file'] = $fileSummary;
        }

        return $fileCollection;
    }


    /**
     * Gets the download link for the files associated to the electrophysiology
     * file (channels.tsv, electrodes.tsv, task events.tsv...)
     *
     * @param int    $physioFileID FileID of the electrophysiology file
     * @param string $physioFile   electrophysiology file's relative path
     *
     * @return array array with the path to the different files associated to the
     *               electrophysiology file
     */
    function getDownloadlinks(int $physioFileID, string $physioFile): array
    {
        $db = \NDB_Factory::singleton()->database();

        $params          = [];
        $params['PFID']  = $physioFileID;
        $downloadLinks   = [];
        $downloadLinks[] = [
            'type' => 'physiological_file',
            'file' => $physioFile,
        ];

        $queries = [
            'physiological_electrode'          => 'physiological_electrode_file',
            'physiological_channel'            => 'physiological_channel_file',
            'physiological_task_event'         => 'physiological_task_event_file',
            'physiological_annotation_archive' => 'physiological_annotation_files',
            'physiological_archive'            => 'all_files',
        ];

        foreach ($queries as $query_key => $query_value) {
            $query_statement = "SELECT
                              DISTINCT(FilePath), '$query_value' AS FileType
                            FROM
                              $query_key
                            WHERE
                              PhysiologicalFileID=:PFID";
            $query_statement = $db->pselectRow($query_statement, $params);
            if (isset($query_statement['FileType'])) {
                $downloadLinks[] = [
                    'type' => $query_statement['FileType'],
                    'file' => $query_statement['FilePath'],
                ];
            } else {
                $downloadLinks[] = [
                    'type' => $query_value,
                    'file' => '',
                ];
            }
        }

        $queryFDT = "SELECT
                   Value AS FilePath,
                   'physiological_fdt_file' AS FileType
                 FROM
                   physiological_parameter_file
                   JOIN parameter_type AS pt USING (ParameterTypeID)
                 WHERE
                   pt.Name='fdt_file'
                   AND PhysiologicalFileID=:PFID";
        $queryFDT = $db->pselectRow($queryFDT, $params);
        if (isset($queryFDT['FileType'])) {
            $downloadLinks[] = [
                'type' => $queryFDT['FileType'],
                'file' => $queryFDT['FilePath'],
            ];
        } else {
            $downloadLinks[] = [
                'type' => 'physiological_fdt_file',
                'file' => '',
            ];
        }

        return $downloadLinks;
    }

    /**
     * Gets the annotation data for the given physiological file
     *
     * @param int $physioFileID Electrophysiological file ID
     *
     * @return array array with the data for each annotation for the file
     */
    function getAnnotations(int $physioFileID): array
    {
        $db = \NDB_Factory::singleton()->database();

        $result         = [];
        $params         = [];
        $params['PFID'] = $physioFileID;

        //Get the annotation file IDs
        $instanceFileID = $db->pselectone(
            'SELECT AnnotationFileID
                FROM physiological_annotation_file
                WHERE PhysiologicalFileID=:PFID
                AND FileType="tsv"',
            $params
        );
        $metadataFileID = $db->pselectone(
            'SELECT AnnotationFileID
                FROM physiological_annotation_file
                WHERE PhysiologicalFileID=:PFID
                AND FileType="json"',
            $params
        );

        //Get the information for each annotation for the given file
        $annotationInstance  = $db->pselect(
            'SELECT * FROM physiological_annotation_instance
                WHERE AnnotationFileID=:instance_FID',
            ['instance_FID' => $instanceFileID]
        );
        $result['instances'] = $annotationInstance;

        //Get the metadata for the annotations of the given file
        $annotationMetadata = $db->pselect(
            'SELECT * FROM physiological_annotation_parameter
                WHERE AnnotationFileID=:metadata_FID',
            ['metadata_FID' => $metadataFileID]
        );
        $result['metadata'] = $annotationMetadata;

        //Get all pre-existing label types
        $annotationLabels = $db->pselect(
            'SELECT * FROM physiological_annotation_label',
            $params
        );
        $result['labels'] = $annotationLabels;

        return $result;
    }

    /**
     * Updates annotation tables when there is a POST request.
     * Will add new derivative files if none exist for the given instance.
     * Will either add new annotations or update existing ones.
     *
     * @param ServerRequestInterface $request POST request from server
     *
     * @return void
     */
    function _updateAnnotation(ServerRequestInterface $request)
    {
        $user = \NDB_Factory::singleton()->user();
        if ($user->hasPermission('electrophysiology_browser_edit_annotations')) {

            $values        = $request->getQueryParams();
            $physioFileID  = $values['physioFileID'];
            $params        = ['PFID' => $physioFileID];
            $instance_data = $values['instance'];
            $db            = \NDB_Factory::singleton()->database();

            $physioFilePath = $db->pselectone(
                'SELECT FilePath
                    FROM physiological_file
                    WHERE PhysiologicalFileID=:PFID',
                $params
            );

            //If the label is new, add to annotation label table
            //and get label ID
            $labelID = $db->pselectone(
                "SELECT AnnotationLabelID
                    FROM physiological_annotation_label
                    WHERE LabelName=:label",
                ['label' => $instance_data['label_name']]
            );
            if (empty($labelID)) {
                $data = [
                    'LabelName'        => $instance_data['label_name'],
                    'LabelDescription' => $instance_data['label_description']
                ];
                $db->insert("physiological_annotation_label", $data);
                $labelID = $db->pselectone(
                    "SELECT AnnotationLabelID
                        FROM physiological_annotation_label
                        WHERE LabelName=:label",
                    ['label' => $instance_data['label_name']]
                );
            }

            //If no derivative files exist, must create new files
            $annotationFID = $db->pselect(
                "SELECT AnnotationFileID
                    FROM physiological_annotation_file
                    WHERE PhysiologicalFileID=:PFID",
                $params
            );

            //Get data from POST request
            $metadata = [
                'Description' => $values['description'],
                'Sources'     => $values['sources'],
                'Author'      => $values['author']
            ];

            $instance = [
                'Onset'             => $instance_data['onset'],
                'Duration'          => $instance_data['duration'],
                'AnnotationLabelID' => $labelID,
                'Channels'          => $instance_data['channels'],
                'AbsoluteTime'      => $instance_data['abs_time'],
                'Description'       => $instance_data['description']
            ];

            //Insert new files and data into DB
            if (empty($annotationFID)) {

                //Create new filepaths
                //Get data directory base path from Config
                $dataDir = $db->pselectone(
                    'SELECT Value
                    FROM Config AS config
                    INNER JOIN ConfigSettings AS c
                    ON c.Name=:name AND config.ConfigID=c.ID',
                    ['name' => 'dataDirBasepath']
                );
                //Create path with correct structure
                $subPath       = strstr($physioFilePath, "sub");
                $pathWithDeriv = $dataDir
                    . "bids_imports/derivatives/loris_annotations/"
                    . $subPath;
                //Create directories if they don't exist
                $dirname = pathinfo($pathWithDeriv, PATHINFO_DIRNAME);
                if (!file_exists($dirname)) {
                    mkdir($dirname, 0777, true);
                    echo "Derivative folder created\n";
                }
                //Replace file type with "annotations"
                $pathWithoutEDF = substr(
                    $pathWithDeriv,
                    0,
                    strrpos($pathWithDeriv, "_")
                );

                $tsv_path  = $pathWithoutEDF . "_annotations.tsv";
                $json_path = $pathWithoutEDF . "_annotations.json";
                $tgz_path  = $pathWithoutEDF . "_annotations.tgz";
                //Create files
                $tsv_file  = fopen($tsv_path, 'a+');
                $json_file = fopen($json_path, 'a+');
                $tgz_file  = new \PharData($tgz_path);
                $tgz_file->addFile($tsv_path, basename($tsv_path));
                $tgz_file->addFile($json_path, basename($json_path));
                fclose($tsv_file);
                fclose($json_file);

                $f    = file_get_contents($tgz_path);
                $hash = sodium_crypto_generichash($f);

                $params_tsv     = [
                    'PhysiologicalFileID' => $physioFileID,
                    'FileType'            => 'tsv',
                    'FilePath'            => str_replace($dataDir, '', $tsv_path)
                ];
                $params_json    = [
                    'PhysiologicalFileID' => $physioFileID,
                    'FileType'            => 'json',
                    'FilePath'            => str_replace($dataDir, '', $json_path),
                ];
                $params_archive = [
                    'PhysiologicalFileID' => $physioFileID,
                    'FilePath'            => str_replace($dataDir, '', $tgz_path),
                    'Blake2bHash'         => bin2hex($hash)
                ];
                $db->insert("physiological_annotation_file", $params_tsv);
                $db->insert("physiological_annotation_file", $params_json);
                $db->insert("physiological_annotation_archive", $params_archive);

                //Get new annotation file ID
                $annotation_tsv_ID = $db->pselectone(
                    "SELECT AnnotationFileID
                        FROM physiological_annotation_file
                        WHERE PhysiologicalFileID=:PFID
                        AND FileType='tsv'",
                    $params
                );
                //Get new annotation file ID
                $annotation_json_ID = $db->pselectone(
                    "SELECT AnnotationFileID
                        FROM physiological_annotation_file
                        WHERE PhysiologicalFileID=:PFID
                        AND FileType='json'",
                    $params
                );
                echo $annotation_json_ID . "\n";
                $metadata['AnnotationFileID'] = $annotation_json_ID;
                $instance['AnnotationFileID'] = $annotation_tsv_ID;

                $db->insert("physiological_annotation_parameter", $metadata);

                //Get new metadata file ID
                $metadata_ID = $db->pselectone(
                    "SELECT AnnotationParameterID
                        FROM physiological_annotation_parameter
                        WHERE AnnotationFileID=:annotation_ID",
                    ['annotation_ID' => $annotation_json_ID]
                );

                $instance['AnnotationParameterID'] = $metadata_ID;

                $db->insert("physiological_annotation_instance", $instance);

            } else {
                //If the files are not new
                //Get annotation file ID for the tsv file
                $tsv_ID = $db->pselectone(
                    "SELECT AnnotationFileID
                        FROM physiological_annotation_file
                        WHERE PhysiologicalFileID=:PFID
                        AND FileType='tsv'",
                    $params
                );
                //Get annotation file ID for the json file
                $json_ID = $db->pselectone(
                    "SELECT AnnotationFileID
                        FROM physiological_annotation_file
                        WHERE PhysiologicalFileID=:PFID
                        AND FileType='json'",
                    $params
                );

                $instance['AnnotationFileID'] = $tsv_ID;
                $metadata['AnnotationFileID'] = $json_ID;

                /* If no instance ID is specified, insert new instance
                 * into instance table and get the parameter file ID
                 * from the parameter table
                 */
                if (empty($values['instance_id'])) {
                    $parameterID = $db->pselectone(
                        "SELECT AnnotationParameterID
                            FROM physiological_annotation_parameter
                            WHERE AnnotationFileID=:annotationFID",
                        ['annotationFID' => $json_ID]
                    );
                    $instance['AnnotationParameterID'] = $parameterID;

                    $db->insert('physiological_annotation_instance', $instance);
                } else {
                    $db->update(
                        'physiological_annotation_instance',
                        $instance,
                        ['AnnotationInstanceID' => $values['instance_id']]
                    );
                }
                //Update parameter table if parameter ID provided
                if (!empty($values['parameter_id'])) {
                    $db->update(
                        'physiological_annotation_parameter',
                        $metadata,
                        ['AnnotationParameterID' => $values['parameter_id']]
                    );
                }

                //In all cases where files are not new,
                //set LastUpdate time for all related files
                $db->update(
                    'physiological_annotation_file',
                    ['LastUpdate' => date("Y-m-d H:i:s")],
                    ['PhysiologicalFileID' => $physioFileID]
                );
            }
        }
    }

    /**
     * Deletes one annotation from a given physiological file
     *
     * @param ServerRequestInterface $request DELETE request from server
     *
     * @return void
     */
    function _deleteAnnotation(ServerRequestInterface $request)
    {
        $user = \NDB_Factory::singleton()->user();
        if ($user->hasPermission('electrophysiology_browser_edit_annotations')) {

            $values = $request->getQueryParams();
            $db     = \NDB_Factory::singleton()->database();
            $params = [
                'AnnotationFileID'     => $values['annotationFileID'],
                'AnnotationInstanceID' => $values['instance_id']
            ];
            $db->delete("physiological_annotation_instance", $params);
        }
    }

    /**
     * Updates the derivative files associated with the given
     * physiological file ID
     *
     * @param int $physioFileID File ID for the annotations to updaete
     *
     * @return void
     * @throws SodiumException
     */
    static function updateDerivativeFiles(int $physioFileID): void
    {
        $db = \NDB_Factory::singleton()->database();

        //Get data directory base path from Config
        $dataDir = $db->pselectone(
            'SELECT Value
        FROM Config AS config
        INNER JOIN ConfigSettings AS c
        ON c.Name=:name AND config.ConfigID=c.ID',
            ['name' => 'dataDirBasepath']
        );

        $tsv_entries = [
            'onset', 'duration', 'label', 'channels', 'aboslute_time', 'description'
        ];

        $params   = ['PFID' => $physioFileID];
        $tsv      = $db->pselect(
            "SELECT 
              AnnotationFileID AS id,
              FilePath         AS filePath,
              LastUpdate       AS lastUpdate
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='tsv'",
            $params
        );
        $json     = $db->pselect(
            "SELECT 
              AnnotationFileID AS id,
              FilePath         AS filePath,
              LastUpdate       AS lastUpdate
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='json'",
            $params
        );
        $tgz_path = $db->pselectone(
            "SELECT FilePath
            FROM physiological_annotation_archive
            WHERE PhysiologicalFileID=:PFID",
            $params
        );

        $tsv_path  = $dataDir.$tsv[0]['filePath'];
        $json_path = $dataDir.$json[0]['filePath'];
        $tgz_path  = $dataDir.$tgz_path;

        $tsv_timestamp  = filemtime($tsv_path);
        $json_timestamp = filemtime($json_path);

        //Update files if files updated before database updated
        if ($tsv_timestamp > $tsv[0]['lastUpdate']
            || $json_timestamp > $json[0]['lastUpdate']
        ) {
            //Update the three files with the given paths
            $labels   = []; // Label Name => Label Description
            $tsv_file = fopen($tsv_path, 'w'); //Will override all file content
            //Add columns
            $columns = implode("\t", $tsv_entries);
            fwrite($tsv_file, $columns."\n");
            //Get all annotation instances
            //Then go thru each and get the label name + description
            //add label name to file and also to an array for json file
            //change anything null to n/a
            $instances = $db->pselect(
                "SELECT
                  p.Onset AS Onset,
                  p.Duration AS Duration,
                  l.LabelName AS LabelName,
                  l.LabelDescription AS LabelDescription,
                  p.Channels AS Channels,
                  p.AbsoluteTime AS AbsoluteTime,
                  p.Description AS Description
                FROM physiological_annotation_instance p
                  LEFT JOIN physiological_annotation_label l 
                    ON (l.AnnotationLabelID=p.AnnotationLabelID)
                WHERE AnnotationFileID=:AFID",
                ['AFID' => $tsv[0]['id']]
            );

            foreach ($instances as $instance) {

                //Add labels to list for parameter file
                $labels[$instance['LabelName']] = $instance['LabelDescription'];

                //Setup each column in correct order
                $input_tsv = [
                    $instance['Onset'],
                    $instance['Duration'],
                    $instance['LabelName'],
                    $instance['Channels'],
                    $instance['AbsoluteTime'],
                    $instance['Description']
                ];
                //Set all null values to 'n/a'
                $input_tsv = array_map(
                    function ($v) {
                        return (is_null($v)) ? "n/a" : $v;
                    },
                    $input_tsv
                );
                //Implode with tabs as delimeter
                $input = implode("\t", $input_tsv);

                fwrite($tsv_file, $input."\n");
            }
            fclose($tsv_file);

            //Write to metadata (json) file
            //Get metadata from database (should only be 1 entry)
            $json_desc   = $db->pselectone(
                "SELECT Description
                FROM physiological_annotation_parameter
                WHERE AnnotationFileID=:AFID",
                ['AFID' => $json[0]['id']]
            );
            $json_source = $db->pselectone(
                "SELECT Sources
                FROM physiological_annotation_parameter
                WHERE AnnotationFileID=:AFID",
                ['AFID' => $json[0]['id']]
            );
            $json_author = $db->pselectone(
                "SELECT Author
                FROM physiological_annotation_parameter
                WHERE AnnotationFileID=:AFID",
                ['AFID' => $json[0]['id']]
            );
            //Get "IntendedFor" entry: physiological file path
            $physioFilePath = $db->pselectone(
                "SELECT FilePath
                FROM physiological_file
                WHERE PhysiologicalFileID=:PFID",
                $params
            );

            $input_json   = [
                "Description"      => $json_desc,
                "IntendedFor"      => $physioFilePath,
                "Sources"          => $json_source,
                "Author"           => $json_author,
                "LabelDescription" => $labels
            ];
            $input_encode = json_encode($input_json, JSON_PRETTY_PRINT);

            $json_file = fopen($json_path, 'w');
            fwrite($json_file, $input_encode);
            fclose($json_file);

            //Make archive tgz and create new hash
            $tgz_file = new \PharData($tgz_path);
            $tgz_file->addFile($tsv_path, basename($tsv_path));
            $tgz_file->addFile($json_path, basename($json_path));

            $f    = file_get_contents($tgz_path);
            $hash = sodium_crypto_generichash($f);
            //Update database with hash
            $db->update(
                'physiological_annotation_archive',
                ['Blake2bHash' => bin2hex($hash)],
                ['PhysiologicalFileID' => $physioFileID]
            );
        }
    }

    /**
     * Writes to any derivative files that are not up to date
     * with the data in the database
     *
     * @return void
     */
    static function updateAllDerivativeFiles() : void
    {
        $db = \NDB_Factory::singleton()->database();

        //Get all file IDs that must be updated
        $physioFileIDs = $db->pselect(
            "SELECT DISTINCT PhysiologicalFileID
            FROM physiological_annotation_file",
            []
        );
        foreach ($physioFileIDs as $id) {
            self::updateDerivativeFiles(intval($id['PhysiologicalFileID']));
        }
    }

    /**
     * Get JS Dependencies
     *
     * @return array of extra JS files that this page depends on
     */
    function getJSDependencies()
    {
        $depends = parent::getJSDependencies();
        $factory = \NDB_Factory::singleton();
        $baseurl = $factory->settings()->getBaseURL();
        $depends = array_merge(
            $depends,
            [
                $baseurl
                . '/electrophysiology_browser/js/electrophysiologySessionView.js',
            ]
        );
        return $depends;
    }

    /**
     * Generate a breadcrumb trail for this page.
     *
     * @return \LORIS\BreadcrumbTrail
     */
    public function getBreadcrumbs(): \LORIS\BreadcrumbTrail
    {
        return new \LORIS\BreadcrumbTrail(
            new \LORIS\Breadcrumb(
                'Electrophysiology Browser',
                '/electrophysiology_browser'
            ),
            new \LORIS\Breadcrumb(
                'View Session',
                "/electrophysiology_browser/sessions/$this->sessionID"
            )
        );
    }

}
