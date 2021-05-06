#!/usr/bin/php
<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once "generic_includes.php";
require_once "Archive/Tar.php";

/**
 * Testing edits to derivative database/files
 *
 * @category   Behavioural
 * @package    Main
 * @subpackage Sessions
 * @author     Loris team <info-loris.mni@mcgill.ca>
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @link       https://github.com/aces/Loris
 */
class TestEditingDerivatives
{

    /**
     *  Testing Blake2bHash generator
     *
     * @return void
     */
    function testHashGenerator()
    {
        $db = \NDB_Factory::singleton()->database();

        $f    = file_get_contents(
            "/data/loris-mri/data/bids_imports/derivatives/"
            ."loris_annotations/sub-DCC0001/ses-V01/ieeg/"
            ."sub-DCC0001_ses-V01_task-test_acq-seeg_annotations.tgz"
        );
        $hash = sodium_crypto_generichash($f);

        $dbHash = $db->pselectone(
            'SELECT Blake2bHash
            FROM physiological_annotation_archive
            WHERE PhysiologicalFileID=:id',
            ['id' => '11']
        );

        if ($hash = $dbHash) {
            echo "Hash generator works\n";
        } else {
            echo "Hash generator does not work\n";
        }
    }


    /**
     * Test that derivative file path is created correctly
     *
     * @return void
     */
    function testFilepathGeneration()
    {
        $db =& \Database::singleton();

        //Add derivative folder to filepath
        $physioFilePath = "bids_imports/__BIDSVersion_1.2.2/sub-DCC0001/"
            ."ses-V01/ieeg/sub-DCC0001_ses-V01_task-test_acq-seeg_ieeg.edf";
        //Get data directory base path from Config
        $dataDirID = $db->pselectone(
            'SELECT ID
            FROM ConfigSettings
            WHERE Name=:name',
            ['name' => 'dataDirBasepath']
        );
        $dataDir   = $db->pselectone(
            'SELECT Value
            FROM Config
            WHERE ConfigID=:id',
            ['id' => $dataDirID]
        );
        //Create path with correct structure
        $subPath       = strstr($physioFilePath, "sub");
        $pathWithDeriv = $dataDir
            ."bids_imports/derivatives/loris_annotations/"
            .$subPath;
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
        $tsv_path       = $pathWithoutEDF."_annotations.tsv";
        $json_path      = $pathWithoutEDF."_annotations.json";
        $tgz_path       = $pathWithoutEDF."_annotations.tgz";
        //Create files
        $tsv_file  = fopen($tsv_path, 'a+');
        $json_file = fopen($json_path, 'a+');
        $tgz_file  = new PharData($tgz_path);
        $tgz_file->addFile($tsv_path, basename($tsv_path));
        $tgz_file->addFile($json_path, basename($json_path));
        fclose($tsv_file);
        fclose($json_file);

        echo "tsv file:".$tsv_path."\n";
        echo "json file:".$json_path."\n";
        echo "tgz file:".$tgz_path."\n";
    }

    /**
     * Test that updating files works by changing data in DB
     * and calling updateDerivativeFiles
     *
     * @return void
     */
    function testAddDataToDB()
    {

        $db =& \Database::singleton();

        $db->insert(
            'physiological_annotation_instance',
            [
                'AnnotationFileID'      => '1',
                'AnnotationParameterID' => '1',
                'AnnotationLabelID'     => '27',
                'Channels'              => 'Channel 7',
                'Description'           => 'Description 7'
            ]
        );
        $db->update(
            'physiological_annotation_parameter',
            [
                'Sources' => 'Source',
                'Author'  => 'First Last'
            ],
            ['AnnotationParameterID' => '1']
        );
        $db->update(
            'physiological_annotation_file',
            ['LastUpdate' => date("Y-m-d H:i:s", mktime(0, 0, 0, 7, 1, 2022))],
            ['PhysiologicalFileID' => 11]
        );
    }

    /**
     * Update files (copied from Sessions.class.inc)
     *
     * @param int|null $physioFileID File ID
     *
     * @return void
     */
    function updateDerivativeFiles(int $physioFileID=null): void
    {
        $db = \NDB_Factory::singleton()->database();

        //Get data directory base path from Config
        $dataDirID = $db->pselectone(
            'SELECT ID
            FROM ConfigSettings
            WHERE Name=:name',
            ['name' => 'dataDirBasepath']
        );
        $dataDir   = $db->pselectone(
            'SELECT Value
            FROM Config
            WHERE ConfigID=:id',
            ['id' => $dataDirID]
        );

        $tsv_entries = [
            'onset', 'duration', 'label', 'channels', 'aboslute_time', 'description'
        ];

        $params      = ['PFID' => $physioFileID];
        $tsv_id      = $db->pselectone(
            "SELECT AnnotationFileID
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='tsv'",
            $params
        );
        $tsv_path    = $db->pselectone(
            "SELECT FilePath
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='tsv'",
            $params
        );
        $tsv_update  = $db->pselectone(
            "SELECT LastUpdate
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='tsv'",
            $params
        );
        $json_id     = $db->pselectone(
            "SELECT AnnotationFileID
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='json'",
            $params
        );
        $json_path   = $db->pselectone(
            "SELECT FilePath
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='json'",
            $params
        );
        $json_update = $db->pselectone(
            "SELECT LastUpdate
            FROM physiological_annotation_file
            WHERE PhysiologicalFileID=:PFID
            AND FileType='json'",
            $params
        );
        $tgz_id      = $db->pselectone(
            "SELECT AnnotationArchiveID
            FROM physiological_annotation_archive
            WHERE PhysiologicalFileID=:PFID",
            $params
        );
        $tgz_path    = $db->pselectone(
            "SELECT FilePath
            FROM physiological_annotation_archive
            WHERE PhysiologicalFileID=:PFID",
            $params
        );

        $tsv_path  = $dataDir.$tsv_path;
        $json_path = $dataDir.$json_path;
        $tgz_path  = $dataDir.$tgz_path;

        $tsv_timestamp  = filemtime($tsv_path);
        $json_timestamp = filemtime($json_path);

        //Update files if files updated before database updated
        if ($tsv_timestamp > $tsv_update
            || $json_timestamp > $json_update
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
                Onset,
                Duration,
                AnnotationLabelID,
                Channels,
                AbsoluteTime,
                Description
                FROM physiological_annotation_instance
                WHERE AnnotationFileID=:AFID",
                ['AFID' => $tsv_id]
            );

            foreach ($instances as $instance) {

                //First, get label name/description
                $label_info = $db->pselectRow(
                    "SELECT LabelName, LabelDescription
                    FROM physiological_annotation_label
                    WHERE AnnotationLabelID=:labelID",
                    ['labelID' => $instance['AnnotationLabelID']]
                );
                $labels[$label_info['LabelName']] = $label_info['LabelDescription'];

                //Setup each column in correct order
                $input_tsv = [
                    $instance['Onset'],
                    $instance['Duration'],
                    $label_info['LabelName'],
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
            $metadata = $db->pselectRow(
                "SELECT Description, Sources, Author
                FROM physiological_annotation_parameter
                WHERE AnnotationFileID=:PFID",
                ['PFID' => $json_id]
            );
            //Get "IntendedFor" entry: physiological file path
            $physioFilePath = $db->pselectone(
                "SELECT FilePath
                FROM physiological_file
                WHERE PhysiologicalFileID=:PFID",
                ['PFID' => $physioFileID]
            );

            $input_json   = [
                "Description"      => $metadata['Description'],
                "IntendedFor"      => $physioFilePath,
                "Sources"          => $metadata['Sources'],
                "Author"           => $metadata['Author'],
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
     * Testing
     *
     * @param array $values Input array with db values
     *
     * @return void
     */
    function _updateAnnotation(array $values)
    {
        //$values        = $request->getQueryParams();
        $physioFileID  = $values['physioFileID'];
        $params        = ['PFID' => $physioFileID];
        $instance_data = $values['instance'];
        $user          = \NDB_Factory::singleton()->user();
        $db            = \NDB_Factory::singleton()->database();

        //if ($this->_hasEditDerivPerms($user)) {

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
            $dataDirID = $db->pselectone(
                'SELECT ID
                FROM ConfigSettings
                WHERE Name=:name',
                ['name' => 'dataDirBasepath']
            );
            $dataDir   = $db->pselectone(
                'SELECT Value
                FROM Config
                WHERE ConfigID=:id',
                ['id' => $dataDirID]
            );
            //Create path with correct structure
            $subPath       = strstr($physioFilePath, "sub");
            $pathWithDeriv = $dataDir
                ."bids_imports/derivatives/loris_annotations/"
                .$subPath;
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
            $tsv_path       = $pathWithoutEDF."_annotations.tsv";
            $json_path      = $pathWithoutEDF."_annotations.json";
            $tgz_path       = $pathWithoutEDF."_annotations.tgz";
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
                'FilePath'            => $tsv_path
            ];
            $params_json    = [
                'PhysiologicalFileID' => $physioFileID,
                'FileType'            => 'json',
                'FilePath'            => $json_path,
            ];
            $params_archive = [
                'PhysiologicalFileID' => $physioFileID,
                'FilePath'            => $tgz_path,
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
            echo $annotation_json_ID."\n";
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
                ['LastUpdate'          => date("Y-m-d H:i:s")],
                ['PhysiologicalFileID' => $physioFileID]
            );
        }
        //}
    }

    /**
     * Testing deleting
     *
     * @param array $values Input array with db values
     *
     * @return void
     */
    function _deleteAnnotation(array $values)
    {
        //$values = $request->getQueryParams();
        $user   = \NDB_Factory::singleton()->user();
        $db     = \NDB_Factory::singleton()->database();
        $params = [
            'AnnotationFileID'     => $values['annotationFileID'],
            'AnnotationInstanceID' => $values['instance_id']
        ];

        //if ($this->_hasEditDerivPerms($user)) {
        $db->delete("physiological_annotation_instance", $params);
        //}
    }
}

$testSessions = new TestEditingDerivatives();
//$testSessions->testFilepathGeneration();
$testSessions->testAddDataToDB();
$physioFileID = 11;
//$testSessions->updateDerivativeFiles($physioFileID);
//$testSessions->testHashGenerator();

//Testing adding a new instance with new label/updating parameter table
$values1 = [
    'physioFileID' => $physioFileID,
    'parameter_id' => 1,
    'description'  => 'A New Description!',
    'sources'      => 'Source Source',
    'author'       => 'Shakespeare',
    'instance'     => [
        'onset'             => 1.222,
        'duration'          => 3.444,
        'label_name'        => 'Fun Label',
        'label_description' => 'Fun Description',
        'channels'          => 'chan1',
        'abs_time'          => null,
        'description'       => 'instance description'
    ]
];
//Test updating an existing annotation
$values2 = [
    'physioFileID' => $physioFileID,
    'instance_id'  => 1,
    'description'  => '',
    'sources'      => '',
    'author'       => '',
    'instance'     => [
        'onset'       => 1.222,
        'duration'    => 3.444,
        'label_name'  => 'Fun Label',
        'channels'    => 'chan1',
        'abs_time'    => null,
        'description' => 'instance description'
    ]
];
//Test creating new annotation files for a physiological file
//that doesn't have any annotation files
$values3 = [
    'physioFileID' => 1,
    'description'  => 'New Description New File!',
    'sources'      => 'New File Source',
    'author'       => 'Shakespeare Again',
    'instance'     => [
        'onset'             => 3.222,
        'duration'          => 5.000,
        'label_name'        => 'Another New Label',
        'label_description' => 'Another New Description',
        'channels'          => 'Test Channel',
        'abs_time'          => null,
        'description'       => 'new instance description'
    ]
];
//Test deleting annotation
$values4 = [
    'annotationFileID' => 1,
    'instance_id'      => 2,
];
//$testSessions->_updateAnnotation($values3);
//$testSessions->_deleteAnnotation($values4);

