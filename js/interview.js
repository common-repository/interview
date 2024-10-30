(function () {

	function isSafari() {
		return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
	}
	//jQuery('.wp-image-712').hide();
	window.interview = JSON.parse(params.interview);
	interview.currentQuestionNumber = 0;
	interview.currentQuestion = interview.questions[interview.currentQuestionNumber];
	if (interview.isOver) {
		displayEnd();
		return;
	}

	function sendEmailInterviewOver() {
		let body = new FormData();
		body.append('action', 'send_email_interview_over');
		body.append('nonce', params.nonce);
		body.append('interviewid', interview.interview.id);
		body.append('email', params.currentUser.email);
		fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
	}

	async function saveQuestion(finish = 0) {
		jQuery.ajax({
			url: params.ajaxurl,
			method: 'POST',
			data: {
				'action':		'interview_save_question',
				'nonce':		params.nonce,
				'userid':		params.currentUser.id,
				'questionid': 	interview.currentQuestion.id,
				'interviewid':	interview.interview.id,
				'answer': 		interview.currentQuestion.answer,
				'filename': 	interview.currentQuestion.filename,
				'finish':		finish
			},
			success: () => {

			},
			error: function (errorThrown) {
				console.log(errorThrown);
			}
		});
	}

	window.updateButtonsDisplay = () => {
		$nextQuestionBtn = jQuery('#nextQuestionBtn');
		$previousQuestionBtn = jQuery('#previousQuestionBtn');
		$endInterviewBtn = jQuery('#endInterviewBtn');
		$nextQuestionBtn.hide();
		$previousQuestionBtn.hide();
		$endInterviewBtn.hide();

		if (interview.currentQuestionNumber < interview.questions.length-1) {
			$nextQuestionBtn.show();
		}
		if (interview.currentQuestionNumber > 0) {
			$previousQuestionBtn.show();
		}
		console.log('interview.currentQuestionNumber', interview.currentQuestionNumber, 'interview.questions.length', interview.questions.length);

		if (interview.currentQuestionNumber >= interview.questions.length-1) {
			$endInterviewBtn.show();
		}
	};

	function displayEnd() {
		jQuery('#interviewContainer').empty().append(params.lang['Thank you']);
	}

	function displayQuestion() {
		let el = `${interview.currentQuestion.question}`;
		let $questions = jQuery('#questions');
		$questions.empty().append(el);
		jQuery('#questionNumber').text(`Question ${interview.currentQuestionNumber + 1}/${interview.questions.length}`);
		$questions.removeClass('blink');
		$questions.addClass('blink');
		updateButtonsDisplay();
	}

	$interviewContainer = jQuery('#interviewContainer');
	if (!interview.interview) {
		throw 'Invalid interview id.';
	}
	let el = `
		<h2>${interview.interview.title}</h2>
		<h3>${interview.interview.description}</h3>
		<h6>${interview.interview.indications}</h6>
		<video id="interviewRecorder" autoplay width="100%"></video>
			<div id="buttonsContainer">
				<button title="Start recording" id="startButton">🔴 Start</button>
				<button title="Stop recording" id="stopButton">⬛ Stop</button>
				<button title="Play the recorded video" id="playbackButton"><span class="dashicons dashicons-controls-play"></span> Playback</button>
			</div>
		<div id="questionsContainer">
			<button class="nextPrevious" title="${params.lang['Previous question']}" id="previousQuestionBtn"><span class="dashicons dashicons-arrow-left-alt"></span></button>
			<div id="questionNumber"></div>
			<div id="questions"></div>
			<button class="nextPrevious" title="${params.lang['Next question']}" id="nextQuestionBtn"><span class="dashicons dashicons-arrow-right-alt"></span></button>
			<button class="nextPrevious" title="${params.lang['End Interview']}" id="endInterviewBtn">👍🏻${params.lang['End Interview']}</span></button>
		</div>
		<div class="answerContainer">
			<textarea name="answer" id="answer" placeholder="${params.lang['You can also answer with text here']}"></textarea>
		</div>`;

	$interviewContainer.append(el);
	displayQuestion();


	jQuery('#questionsContainer').on('click', 'button#nextQuestionBtn', function () {
		let $answer = jQuery('#answer');
		interview.questions[interview.currentQuestionNumber].answer = $answer.val();
		saveQuestion();
		interview.currentQuestionNumber++;
		interview.currentQuestion = interview.questions[interview.currentQuestionNumber];
		$answer.val(interview.questions[interview.currentQuestionNumber].answer);
		if (interview.questions[interview.currentQuestionNumber].filename) {
			interviewRecorder.src = interview.path + interview.questions[interview.currentQuestionNumber].filename;
			interviewRecorder.pause();
			console.log('interviewRecorder.src', interviewRecorder.src);
		} else {
			interviewRecorder.src = ''
		}
		displayQuestion();
	}).on('click', 'button#previousQuestionBtn', function () {
		let $answer = jQuery('#answer');
		interview.questions[interview.currentQuestionNumber].answer = $answer.val();
		interview.currentQuestion = interview.questions[--interview.currentQuestionNumber];
		$answer.val(interview.questions[interview.currentQuestionNumber].answer);
		if (interview.questions[interview.currentQuestionNumber].filename) {
			interviewRecorder.src = interview.path + interview.questions[interview.currentQuestionNumber].filename;
			interviewRecorder.pause();
			console.log('interviewRecorder.src', interviewRecorder.src);
		} else {
			interviewRecorder.src = ''
		}
		displayQuestion();
	}).on('click', 'button#endInterviewBtn', function () {
		if (confirm(`${params.lang['Finish this interview ?']}`)) {
			interview.currentQuestion.answer = jQuery('#answer').val();
			saveQuestion();
			$r.reset();
			displayEnd();
			sendEmailInterviewOver();
		}
	});

	console.log('filename', interview.questions[interview.currentQuestionNumber].filename);
	let $r = jQuery('#interviewRecorder').videoRecorder({
		"startBtn": "startButton",
		"stopBtn": "stopButton",
		"playbackBtn": "playbackButton",
		"maxLength": parseInt(interview.currentQuestion.secondsMax),
		"countDown": parseInt(interview.currentQuestion.secondsBeforeStart),
		"autoStart": false,
		"uploadURL": params.uploadURL,
		"onStop": function (settings) {
			interviewRecorder.src = interview.path + interview.questions[interview.currentQuestionNumber].filename;
			interviewRecorder.muted = false;
			interviewRecorder.autoplay = false;
			interviewRecorder.pause();
		},
		"onStart": function (settings) {
			let extension = isSafari()?'mp4':'webm';
			interview.questions[interview.currentQuestionNumber].filename = interview.questions[interview.currentQuestionNumber].filename ?? `${Date.now()}.${extension}`;
			$r.setFileName(interview.questions[interview.currentQuestionNumber].filename);
			console.log('onStart interview.currentQuestionNumber', interview.currentQuestionNumber, 'interview.questions.length', interview.questions.length);

		}
	});
})(params);