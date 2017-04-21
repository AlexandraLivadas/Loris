!function(e){function t(r){if(a[r])return a[r].exports;var n=a[r]={exports:{},id:r,loaded:!1};return e[r].call(n.exports,n,n.exports,t),n.loaded=!0,n.exports}var a={};return t.m=e,t.c=a,t.p="",t(0)}([function(e,t,a){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}var n=a(2),o=r(n);$(function(){var e=React.createElement("div",{className:"page-imaging-uploader"},React.createElement(o.default,{Module:"imaging_uploader",DataURL:loris.BaseURL+"/imaging_uploader/?format=json"}));ReactDOM.render(e,document.getElementById("lorisworkspace"))})},,function(e,t,a){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function o(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function l(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var i=function(){function e(e,t){for(var a=0;a<t.length;a++){var r=t[a];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,a,r){return a&&e(t.prototype,a),r&&e(t,r),t}}(),s=a(3),c=r(s),u=a(4),d=r(u),f=a(5),p=r(f),m=function(e){function t(e){n(this,t);var a=o(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return a.state={isLoaded:!1,filter:{}},a.fetchData=a.fetchData.bind(a),a.updateFilter=a.updateFilter.bind(a),a}return l(t,e),i(t,[{key:"componentDidMount",value:function(){this.fetchData()}},{key:"fetchData",value:function(){$.ajax(this.props.DataURL,{method:"GET",dataType:"json",success:function(e){this.setState({Data:e,isLoaded:!0})}.bind(this),error:function(e){console.error(e)}})}},{key:"updateFilter",value:function(e){this.setState({filter:e})}},{key:"render",value:function(){if(!this.state.isLoaded)return React.createElement("button",{className:"btn-info has-spinner"},"Loading",React.createElement("span",{className:"glyphicon glyphicon-refresh glyphicon-refresh-animate"}));var e=[{id:"browse",label:"Browse"},{id:"upload",label:"Upload"}];return React.createElement(Tabs,{tabs:e,defaultTab:"browse",updateURL:!0},React.createElement(TabPane,{TabId:e[0].id},React.createElement("div",{className:"row"},React.createElement("div",{className:"col-md-5"},React.createElement(FilterForm,{Module:"imaging_uploader",name:"imaging_filter",id:"imaging_filter",onUpdate:this.updateFilter,filter:this.state.filter},React.createElement(TextboxElement,this.state.Data.form.candID),React.createElement(TextboxElement,this.state.Data.form.pSCID),React.createElement(SelectElement,this.state.Data.form.visitLabel),React.createElement(ButtonElement,{type:"reset",label:"Clear Filters"}))),React.createElement("div",{className:"col-md-7"},React.createElement(c.default,null))),React.createElement("div",{id:"mri_upload_table"},React.createElement(StaticDataTable,{Data:this.state.Data.Data,Headers:this.state.Data.Headers,getFormattedCell:p.default,Filter:this.state.filter}))),React.createElement(TabPane,{TabId:e[1].id},React.createElement(d.default,{form:this.state.Data.form})))}}]),t}(React.Component);m.propTypes={},m.defaultProps={},t.default=m},function(e,t){"use strict";function a(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function r(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function n(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var o=function(){function e(e,t){for(var a=0;a<t.length;a++){var r=t[a];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,a,r){return a&&e(t.prototype,a),r&&e(t,r),t}}(),l=function(e){function t(e){a(this,t);var n=r(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.state={logText:"<select a row in the table below to view the upload logs>",logType:"summary"},n.initHelper=n.initHelper.bind(n),n.onLogTypeChange=n.onLogTypeChange.bind(n),n.setServerPolling=n.setServerPolling.bind(n),n.monitorProgress=n.monitorProgress.bind(n),n}return n(t,e),o(t,[{key:"componentDidMount",value:function(){this.initHelper()}},{key:"initHelper",value:function(){var e=new UploadProgress;this.uploadProgress=e,$("#mri_upload_table").on("click","tbody tr",function(t){return null!==e.getUploadRow()&&($(e.getUploadRow()).css("background-color","white"),this.setServerPolling(!1)),t.currentTarget===e.getUploadRow()?(e.setUploadRow(null),e.setProgressFromServer(null),void this.setState({logText:"<select a row in the table below to view the upload logs>"})):(e.setUploadRow(t.currentTarget),$(t.currentTarget).css("background-color","#EFEFFB"),void this.monitorProgress(this.state.logType))}.bind(this))}},{key:"monitorProgress",value:function(e){var t="summary"===e,a=this.uploadProgress,r=a.getUploadId();r&&$.post(loris.BaseURL+"/imaging_uploader/ajax/getUploadSummary.php",{uploadId:r,summary:t},function(e){a.setProgressFromServer(e),this.setState({logText:a.getProgressText()}),this.setServerPolling(a.getPipelineStatus()===UploadProgress.PIPELINE_STATUS_RUNNING)}.bind(this))}},{key:"setServerPolling",value:function(e){var t=this.uploadProgress;e?(this.setServerPolling.getSummaryInterval||(this.setServerPolling.getSummaryInterval=setInterval(this.monitorProgress,5e3)),this.setServerPolling.dotUpdateInterval||(this.setServerPolling.dotUpdateInterval=setInterval(function(){t.updateDots(),this.setState({logText:t.getProgressText()})},3e3)),this.setServerPolling.animatedCharInterval||(this.setServerPolling.animatedCharInterval=setInterval(function(){t.updateAnimatedCharIndex(),this.setState({logText:t.getProgressText()})},250))):(this.setServerPolling.getSummaryInterval&&(clearInterval(this.setServerPolling.getSummaryInterval),this.setServerPolling.getSummaryInterval=null),this.setServerPolling.dotUpdateInterval&&(clearInterval(this.setServerPolling.dotUpdateInterval),this.setServerPolling.dotUpdateInterval=null),this.setServerPolling.animatedCharInterval&&(clearInterval(this.setServerPolling.animatedCharInterval),this.setServerPolling.animatedCharInterval=null))}},{key:"onLogTypeChange",value:function(e,t){this.monitorProgress(t),this.setState({logType:t})}},{key:"render",value:function(){var e={summary:"Summary",detailed:"Detailed"};return React.createElement(Panel,{id:"log_panel",title:"Log Viewer"},React.createElement(FormElement,{name:"log_form"},React.createElement(SelectElement,{name:"LogType",label:"Logs to display",options:e,onUserInput:this.onLogTypeChange,value:this.state.logType,emptyOption:!1}),React.createElement(TextareaElement,{name:"UploadLogs",disabled:!0,id:"mri_upload_logs",value:this.state.logText,rows:6})))}}]),t}(React.Component);l.propTypes={},l.defaultProps={},t.default=l},function(e,t){"use strict";function a(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function r(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function n(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var o=function(){function e(e,t){for(var a=0;a<t.length;a++){var r=t[a];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,a,r){return a&&e(t.prototype,a),r&&e(t,r),t}}(),l=function(e){function t(e){a(this,t);var n=r(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e));return n.state={formData:{},form:JSON.parse(JSON.stringify(n.props.form)),uploadProgress:-1},n.onFormChange=n.onFormChange.bind(n),n.uploadFile=n.uploadFile.bind(n),n}return n(t,e),o(t,[{key:"componentDidMount",value:function(){var e=this.state.form;e.IsPhantom.required=!0,this.onFormChange(e.IsPhantom.name,null)}},{key:"onFormChange",value:function(e,t){var a=JSON.parse(JSON.stringify(this.state.form)),r=JSON.parse(JSON.stringify(this.state.formData));"IsPhantom"===e&&("N"===t?(a.candID.disabled=!1,a.pSCID.disabled=!1,a.visitLabel.disabled=!1):(a.candID.disabled=!0,a.pSCID.disabled=!0,a.visitLabel.disabled=!0,delete r.candID,delete r.pSCID,delete r.visitLabel)),r[e]=t,this.setState({form:a,formData:r})}},{key:"uploadFile",value:function(){var e=this.state.formData,t=new FormData;for(var a in e)""!==e[a]&&t.append(a,e[a]);t.append("fire_away","Upload"),$.ajax({type:"POST",url:loris.BaseURL+"/imaging_uploader/",data:t,cache:!1,contentType:!1,processData:!1,xhr:function(){var e=new window.XMLHttpRequest;return e.upload.addEventListener("progress",function(e){if(e.lengthComputable){var t=Math.round(e.loaded/e.total*100);this.setState({uploadProgress:t})}}.bind(this),!1),e}.bind(this),success:function(e){var t="The following errors occured while attempting to display this page:";e.indexOf(t)>-1?(document.open(),document.write(e),document.close()):swal({title:"Upload Successful!",type:"success"},function(){window.location.assign(loris.BaseURL+"/imaging_uploader/")})},error:function(e){console.error(e),this.setState({uploadProgress:-1})}.bind(this)})}},{key:"render",value:function(){var e=this.state.form;e.IsPhantom.value=this.state.formData.IsPhantom,e.candID.value=this.state.formData.candID,e.pSCID.value=this.state.formData.pSCID,e.visitLabel.value=this.state.formData.visitLabel,e.mri_file.value=this.state.formData.mri_file;var t=this.state.uploadProgress>-1?"btn btn-primary hide":void 0;return React.createElement("div",{className:"row"},React.createElement("div",{className:"col-md-7"},React.createElement("h3",null,"Upload an imaging scan"),React.createElement("br",null),React.createElement(FormElement,{name:"upload_form",formElements:e,fileUpload:!0,onUserInput:this.onFormChange},React.createElement(StaticElement,{label:"Notes",text:"File name should be of type .tgz or tar.gz or .zip"}),React.createElement("div",{className:"row"},React.createElement("div",{className:"col-sm-9 col-sm-offset-3"},React.createElement(ProgressBar,{value:this.state.uploadProgress}))),React.createElement(ButtonElement,{onUserInput:this.uploadFile,buttonClass:t}))))}}]),t}(React.Component);l.propTypes={},l.defaultProps={},t.default=l},function(e,t){"use strict";function a(e,t,a,r){function n(e,t){loris.loadFilteredMenuClickHandler("imaging_browser/",{DCCID:e})(t)}if(loris.hiddenHeaders.indexOf(e)>-1)return null;var o={};r.forEach(function(e,t){o[e]=a[t]},this);var l={whiteSpace:"nowrap"};if("Progress"===e){if("Failure"===t)return l.color="#fff",React.createElement("td",{className:"label-danger",style:l},t);if("In Progress..."===t)return l.color="#fff",React.createElement("td",{className:"label-warning",style:l},t);var i=o["Number Of MincCreated"],s=o["Number Of MincInserted"];return React.createElement("td",{style:l},t," (",s," out of ",i,")")}if("Tarchive Info"===e){if(!t||"0"===t)return React.createElement("td",null);var c=loris.BaseURL+"/dicom_archive/viewDetails/?tarchiveID="+t;return React.createElement("td",{style:l},React.createElement("a",{href:c},"View Details"))}return"Number Of MincInserted"===e&&t>0?React.createElement("td",{style:l},React.createElement("a",{onClick:n.bind(null,o.CandID)},t)):React.createElement("td",{style:l},t)}Object.defineProperty(t,"__esModule",{value:!0}),loris.hiddenHeaders=[],t.default=a}]);
//# sourceMappingURL=index.js.map