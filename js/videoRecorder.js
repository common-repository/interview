(function ($) {

	function isSafari() {
		return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
	}

	$.fn.videoRecorder = function (options) {
		let $videoElement = this;
		if ($videoElement[0].nodeName !== 'VIDEO') {
			throw (`Element is not VIDEO. Ex: <video id="videoRecorder" muted autoplay width="100%" height="100%"></video>`);
		}
		if (!options.filename) {
			console.log("No 'filename' given. Random filename added.");
		}
		let countDownInterval;
		let intervalRecorder;
		let mediaRecorder;
		let chunks = [];
		let localStream;
		let countDown;
		let autoStart = false;
		let settings = $.extend({
			startBtn: 'startBtn',
			stopBtn: 'stopBtn',
			playbackBtn: 'playbackBtn',
			maxLength: 10,
			countDown: 0,
			filename: `${Date.now()}.webm`,
			videoSecondsLength: 0,
			recorderOptions: {
				mimeType: isSafari()? 'video/mp4' : 'video/webm;codecs=vp8,opus',
				videoBitsPerSecond: 300000
			},
			constraints: {
				audio: true,
				video: true,
			},
			uploadURL: '',
			onStart: onStartCallBack,
			onStop: onStopCallBack,

		}, options);
		console.log(settings.recorderOptions.mimeType);

		function onStopCallBack() {
			console.log('onStopCallBack');
		}

		function onStartCallBack() {
			console.log('onStartCallBack');
		}

		function reset() {
			chunks = [];
			jQuery('#counterDownRecorder').remove();
			jQuery('#counterDownRecorder2').remove();
			countDown = settings.countDown;
			settings.videoSecondsLength = 0;
			$stopBtn[0].disabled = true;
			$startBtn[0].disabled = false;
			$playbackBtn.hide();
		}

		let $startBtn = $(`#${settings.startBtn}`);
		let $stopBtn = $(`#${settings.stopBtn}`);
		let $playbackBtn = $(`#${settings.playbackBtn}`);
		$stopBtn[0].disabled = true;
		$playbackBtn.hide();

		function getUserMediaError(error) {
			console.error('Rejected!', error);
		}

		$startBtn.on('click', async () => {
			reset();
			//let resOnStart = settings.onStart(settings);
			let resOnStart = settings.onStart;
			if (resOnStart === false) {
				return;
			}
			settings.onStart(settings);

			$startBtn[0].disabled = true;
			await navigator.mediaDevices.getUserMedia(settings.constraints).then(getUserMediaSuccess).catch(getUserMediaError);
			if (settings.countDown) {
				$videoElement.wrapAll('<div id="videoWrapper" style="display: inline-block;position: relative;">');
				jQuery('<div/>', {
					id: 'counterDownRecorder',
					css: {
						'font-size': '2.5em',
						'color': 'white',
						'position': 'absolute',
						'display': 'flex',
						'justify-content': 'center',
						'align-items': 'center',
						'top': 0,
						'left': 0,
						'right': 0,
						'bottom': 0,
						'textShadow': '#000 6px 6px 6px',
						'background': 'grey',
					}
				}).appendTo('#videoWrapper');

				jQuery('<div/>', {
					id: 'counterDownRecorder2',
				}).appendTo('#videoWrapper');
				countDownInterval = setInterval(() => countDownFunction(), 1000);
			} else {
				stepAskAccessWebcam();
			}
		});
		$stopBtn.on('click', async () => {
			stopRecording();
		});
		$playbackBtn.on('click', async () => {
			playBackVideo();
		});

		function playBackVideo() {
			$videoElement[0].muted = false;
			if($videoElement[0].src.indexOf('blob:')===0) {
				$videoElement[0].src = '';
				let blob = new Blob(chunks, settings.recorderOptions);
				$videoElement[0].src = window.URL.createObjectURL(blob);
				$videoElement[0].controls = true;
				$videoElement[0].load();
			} else {
				$videoElement[0].src = $videoElement[0].src + '?cache='+ Date.now();
				$videoElement[0].controls = true;
				$videoElement[0].play();
			}
		}

		if (settings.autoStart) {
			$startBtn.click();
		}

		async function stopRecording() {
			jQuery('#counterDownRecorder').remove();
			jQuery('#counterDownRecorder2').remove();
			settings.onStop(settings);
			clearInterval(intervalRecorder);
			console.log('stopRecording');
			$stopBtn[0].disabled = true;
			$playbackBtn.show();
			$startBtn[0].disabled = false;
			localStream.getTracks().forEach(track => track.stop());
			mediaRecorder.stop();
			$videoElement[0].srcObject = null;
			delete $videoElement[0].src;
			$videoElement[0].load();
		}

		function getUserMediaSuccess(stream) {
			localStream = stream;
			$videoElement[0].srcObject = stream;
			$videoElement[0].autoplay = true;
			$videoElement[0].muted = true;
			$videoElement[0].controls = true;
			mediaRecorder = new MediaRecorder(stream, settings.recorderOptions);
			console.log('mediaRecorder.mimeType:', mediaRecorder.mimeType);
		}

		if (!$startBtn.length) throw 'Error : startBtn is not present on the page';

		function countDownFunction() {
			if (!countDown) {
				clearInterval(countDownInterval);
				stepAskAccessWebcam();
			}
			$('#counterDownRecorder').text(countDown);
			countDown--;
		}

		function stepAskAccessWebcam() {
			console.log('stepAskAccessWebcam');
			$('#counterDownRecorder').remove();
			$stopBtn[0].disabled = false;
			let recordingLength = settings.maxLength;
			intervalRecorder = setInterval(() => {
				recordingLength--;
				jQuery('#counterDownRecorder2').text(recordingLength);
				if (!recordingLength) {
					clearInterval(intervalRecorder);
					stopRecording();
				}
			}, 1000);
			let order = 0;
			mediaRecorder.ondataavailable = async (e) => {
				console.log('ondataavailable');
				settings.videoSecondsLength++;
				if (e.data && e.data.size > 0) {
					chunks.push(e.data);
					let reader = new FileReader();
					reader.readAsArrayBuffer(e.data);
					reader.onloadend = async function () {
						let arrayBuffer = reader.result;
						let uint8View = new Uint8Array(arrayBuffer);
						await fetch(settings.uploadURL, {
							method: 'POST',
							body: JSON.stringify({
								chunk: uint8View,
								order: order,
								filename: settings.filename,
								action: 'recorder_save_video',
							})
						});
						console.log('saving', settings.filename, order);
						order += 1;
					}
				}
			};
			mediaRecorder.start(500);
		}

		this.setFileName = function (filename) {
			settings.filename = filename;
		};


		this.reset = function () {
			reset();
		};
		this.stop = function () {
			clearInterval(countDown);
			clearInterval(intervalRecorder);
			mediaRecorder.stop()

		};
		return this;
	};
}(jQuery));