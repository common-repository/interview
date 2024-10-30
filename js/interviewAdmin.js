(function (params) {
	let page = 0;
	drawTable(page);

	async function drawTable(page) {
		let body = new FormData();
		body.append('action', 'interview_get_records');
		body.append('nonce', params.nonce);
		body.append('page', page);
		let response = await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		let res = await response.json();
		let trs = '';
		let $interviewTableContainer = jQuery('#interviewTableContainer');
		$interviewTableContainer.empty();
		let numberPages = 1 + Math.floor((parseInt(res.numberRows) - 1) / parseInt(res.ROWS_PER_PAGE));
		let rows = res.rows;

		rows.forEach(function (data) {
			let usersInterviews = '';
			if (data.users) {
				let emails = data.users.split(',');
				emails.forEach(function (email) {
					usersInterviews = usersInterviews + `
				<div>
					<button title="${params.lang['Play/preview interview']}" data-interviewaction="playInterviewBtn" class="playInterviewBtn" data-email="${email}" data-interviewid="${data['id']}">
						<span class="dashicons dashicons-controls-play"></span> ${params.lang['Watch']} ${email} 
					</button>
					<button title="${params.lang['Publish interview as post in draft']}"  data-email="${email}" data-interviewid="${data['id']}" data-interviewaction="publishInterviewBtn" class="playInterviewBtn" class="playInterviewBtn" title="Publish this interview into a post">
						<span class="dashicons dashicons-format-aside"></span> ${params.lang['Publish']} ${email} 
					</button>
				</div>
				`;
				});
			}
			trs += `<tr>
						<td style="width: 40px;">${data['id']}</td>
						<td>
							<div>
								<div><b>${data['title']}</b></div>
								<span class="shortcode" title="${params.lang['Paste that shortcode to post/page']}'">[interview id=${data['id']}]</span>
							</div>
							<div class="buttonsPlayContainer">
								${usersInterviews}
							</div>
						</td>
						<td style="width: 150px">${data['date']}</td>                                
						<td style="width: 300px;">
							<button data-id="${data['id']}" data-interviewaction="edit" title="${params.lang['Edit']} interview"><span class="dashicons dashicons-edit"></span> ${params.lang['Edit']}</button>
							<button data-id="${data['id']}" data-interviewaction="delete" title="${params.lang['Delete']} interview"><span class="dashicons dashicons-trash"></span> ${params.lang['Delete']}</button>							
							<button data-id="${data['id']}" data-interviewaction="duplicate" title="${params.lang['Copy']} interview"><span class="dashicons dashicons-admin-page"></span> ${params.lang['Copy']}</button>							
							<button data-id="${data['id']}" data-interviewaction="invite" title="${params.lang['Invite user per email for an interview']}"><span class="dashicons dashicons-email-alt"></span> ${params.lang['Invite']}</button>							
						</td>
					</tr>`;
		});
		let table = `
                <table id="interviewTable">
                <tr>
                    <th>Id</th>
                    <th>${params.lang['Title']}</th>
                    <th>${params.lang['Date']}</th>                    
                    <th>${params.lang['Actions']}</th>
                </tr>
                ${trs}       
                </table>`;
		table += `
                <div>
                    <span>Total: <b>${res.numberRows}</b></span> `;
		if (numberPages > 1) {
			let options = '';
			for (let i = 0; i < numberPages; i++) {
				let selected = (parseInt(page) === i) ? 'selected' : '';
				options += `<option value="${i}" ${selected} >${i + 1}</option>`;
			}
			table += `
                        <div>
                            Page:
                            <select id="interviewSelectPage">
                            ${options}
                            </select>
                         </div>                   
                </div>`;
		}
		$interviewTableContainer.html(table);
	}

	function getQuestionElementTemplate(question) {
		if (!question) {
			let max = 0;
			jQuery('#questionsContainer div.questionItem').each(function(index, value) {
				let id = parseInt(jQuery(this).data('id'));
				if (id>max) max = id;
			});
			question = {id:0, ordre:99, question:'', secondsMax:60};
		}
		let durationId	= `durationid${Math.random()}`;

		return `<div class="questionItem" data-id="${question.id}">
				<input type="hidden" name="ordre[]" value="${question.ordre}">
				<input type="hidden" name="ids[]" value="${question.id}">				
					<div>
						<input class="questionAdmin" type="text" value="${question.question}" required placeholder="${params.lang['Enter your question here']}" name="questions[]">
					</div>
					<div style="width: 400px;">
						<div>
							<label for="${durationId}">${params.lang['Duration']}</label>	
							<input id="${durationId}" type="number" name="secondsMax[]" title="${params.lang['Webcam Recording: maximum duration is seconds']}" placeholder="${params.lang['Seconds']}" required min="1" value="${question.secondsMax}" style="width: 60px;">
						</div>
						<div>
							<button class="deleteQuestion"><span class="dashicons dashicons-trash"></span> Delete</button>
						</div>
					</div>
			</div>`;
	}

	jQuery(document).on('change', '#interviewSelectPage', function () {
		page = jQuery(this).val();
		drawTable();
	}).on('click', '.deleteQuestion', function () {
		jQuery(this).parent().parent().parent().remove();
	});

	jQuery('label[for="inviteEmails"]').click(async ()=> {
		jQuery('#inviteEmails').val(params.email);
	});

	jQuery('#inviteInterviewSendBtn').click(async ()=> {
		if (!formInviteInterview.reportValidity()) {
			return;
		}
		let body = new FormData();
		body.append('action', 'interview_invite');
		body.append('nonce', params.nonce);
		body.append('message', inviteInterviewMessage.value);
		body.append('inviteInterviewId', inviteInterviewId.value);
		body.append('subject', inviteSubject.value);
		body.append('inviteEmails', inviteEmails.value);
		//body.append('invitation', jQuery('#inviteInterviewMessage').summernote('code'));

		await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		jQuery("#TB_closeWindowButton").click();
		alert('Interview request sent.');
	});

	jQuery('#saveEditInterviewBtn').click(async ()=> {
		if (!formEditInterview.reportValidity()) {
			return;
		}
		let body = new FormData(formEditInterview);
		body.append('id', editInterviewId.value);
		body.append('action', 'interview_update');
		body.append('nonce', params.nonce);
		await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		jQuery("#TB_closeWindowButton").click();
		drawTable();
	});

	jQuery('#saveInterviewBtn').click(async ()=> {
		if (!formAddInterview.reportValidity()) {
			return;
		}
		let body = new FormData(formAddInterview);
		body.append('action', 'interview_insert');
		body.append('nonce', params.nonce);
		let response = await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		jQuery("#TB_closeWindowButton").click();
		let result = await response.text();
		//console.log('result', result);
		drawTable();
	});

	jQuery('#editAddInterviewQuestion').click(()=> {
		jQuery('#editQuestionsContainer').append(getQuestionElementTemplate());
	});

	jQuery('#addInterviewQuestion').click(()=> {
		jQuery('#questionsContainer').append(getQuestionElementTemplate());
	});

	jQuery('#langSelection').on('change', async (e)=> {
		let lang = jQuery(e.currentTarget).val();
		let body = new FormData();
		body.append('action', 'interview_change_lang');
		body.append('lang', lang);
		body.append('nonce', params.nonce);
		let response = await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		window.location = window.location;

	});


	jQuery('#questionsContainer').sortable({
		update: (event, ui) => {
			jQuery('.questionItem input[type=hidden]').each(function (index) {
				jQuery(this).val(index);
			});
		}
	});
	jQuery('#editQuestionsContainer').sortable({
		update: (event, ui) => {
			jQuery('.questionItem input[type=hidden]').each(function (index) {
				jQuery(this).val(index);
			});
		}
	});


	jQuery('#interviewAddNewBtn').click(() => {
		tb_show('New Interview', '#TB_inline', '');
		if (!jQuery("#questionsContainer div").length) {
			jQuery('#addInterviewQuestion').click();
		}
	});


	jQuery(document).on('click', '.shortcode',  (e)=> {
		let text = e.currentTarget.innerText;
		navigator.clipboard.writeText(text).then(function() {
		}, function(err) {
			console.error('Async: Could not copy text: ', err);
		});
	});
	jQuery(document).on('click', 'button[data-interviewaction="duplicate"]',  async (e)=> {
		if (confirm('Duplicate ?')) {
			let id = jQuery(e.currentTarget).data('id');
			let body = new FormData();
			body.append('action', 'interview_duplicate_record');
			body.append('nonce', params.nonce);
			body.append('id', id);
			await fetch(params.ajaxurl, {
				method: 'POST',
				body: body
			});
			drawTable();
		}
	});

	jQuery(document).on('click', 'button[data-interviewaction="publishInterviewBtn"]',  async (e)=> {
		if (confirm('Publish / Create a new post with this interview as post content ? (The published interview will be published as draft post so you can edit it before publishing)')) {
			let interviewid = jQuery(e.currentTarget).data('interviewid');
			let email 		= jQuery(e.currentTarget).data('email');
			let body 		= new FormData();
			body.append('action', 'interview_create_post');
			body.append('nonce', params.nonce);
			body.append('interviewid', interviewid);
			body.append('email', email);
			let res = await fetch(params.ajaxurl, {
				method: 'POST',
				body: body
			});
			let link = await res.text();
			window.open(link, '_blank').focus();
		}
	});


	jQuery(document).on('click', 'button[data-interviewaction="delete"]',  async (e)=> {
		if (confirm('Delete ?')) {
			let id = jQuery(e.currentTarget).data('id');
			let body = new FormData();
			body.append('action', 'interview_delete_record');
			body.append('nonce', params.nonce);
			body.append('id', id);
			await fetch(params.ajaxurl, {
				method: 'POST',
				body: body
			});
			drawTable();
		}
	});

	jQuery(document).on('click', '#interviewTable [data-interviewaction="playInterviewBtn"]',  async (e)=> {
		let interviewid = jQuery(e.currentTarget).data('interviewid');
		let email = jQuery(e.currentTarget).data('email');
		console.log(interviewid, email);
		tb_show('Interview preview', '#TB_inline?inlineId=playInterviewContainer', '');
		let body = new FormData();
		body.append('action', 'interview_get_with_answers');
		body.append('nonce', params.nonce);
		body.append('interviewid', interviewid);
		body.append('email', email);
		let response = await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		let template = await response.text();

		jQuery('#playInterviewContent').empty().html(template).prepend(
		`<div>To publish that interview into one of your page or post, just paste that shortcode:</div>
		<div class="shortcode" title="click to copy to clipboard">[interviewPublish id=${interviewid} email=${email}]</div>`);

	});

	jQuery(document).on('click', '#interviewTable [data-interviewaction="invite"]',  async (e)=> {
		let interviewid = jQuery(e.currentTarget).data('id');
		inviteInterviewId.value = interviewid;
		console.log('invite', interviewid);
		tb_show("🎙️ Invite interview","#TB_inline?inlineId=inviteInterviewContainer",null);

		let body = new FormData();
		body.append('action', 'interview_get');
		body.append('nonce', params.nonce);
		body.append('id', interviewid);
		let response = await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		let res = await response.json();
		let interview = res.interview;
		//let questions = res.questions;

		if (!inviteSubject.value) {
			inviteSubject.value = interview.title;
		}
		if (!inviteInterviewMessage.value) {
			inviteInterviewMessage.value = interview.invitation;
		}
		jQuery('#inviteInterviewMessage').summernote();
	});

	jQuery(document).on('click', '#interviewTable [data-interviewaction="edit"]',  async (e)=> {
		let id = jQuery(e.currentTarget).data('id');
		let body = new FormData();
		body.append('action', 'interview_get');
		body.append('nonce', params.nonce);
		body.append('id', id);
		let response = await fetch(params.ajaxurl, {
			method: 'POST',
			body: body
		});
		let res = await response.json();
		let interview = res.interview;
		let questions = res.questions;
		tb_show('Edit interview','#TB_inline?inlineId=editInterviewContainer', null);
		editInterviewId.value = interview.id;
		editInterviewTitle.value = interview.title;
		editInterviewDescription.value = interview.description;
		editInterviewIndications.value = interview.indications;
		$editQuestionsContainer = jQuery('#editQuestionsContainer');
		$editQuestionsContainer.empty();
		for (const question of questions) {
			$editQuestionsContainer.append(getQuestionElementTemplate(question));
		}
	});

	jQuery(document).on('click', '#interviewTable [data-interviewaction="play"]', function () {
		tb_show('Player', '#TB_inline', '');
	});

	jQuery('#interviewDeleteAllrecords').click(function () {
		if (confirm('Are you totally sure to delete ALL records ?')) {
			jQuery.ajax({
				url: params.ajaxurl,
				method: 'POST',
				data: {
					'action': 'screenRecorder_delete_all_records',
					'nonce': params.nonce,
				},
				success: function () {
					drawTable();
				},
				error: function (errorThrown) {
					console.log(errorThrown);
				}
			});
		}
	})
})(params);