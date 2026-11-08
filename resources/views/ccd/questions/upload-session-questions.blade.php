@extends('ccd.layout')

@section('dashboard_content')

<div>
	@include('ccd._breadcrumb', ['headerTitle' => 'Upload Session Questions'])
	<div class="justify-content-center mt-4">
		<div class="tile">
			<div class="tile-title">Paste Questions in Segments</div>
			<p class="mb-3">
				Copy a manageable batch of questions from your document into each editor below.
				Each segment is processed separately so large question banks do not have to be sent to AI in one request.
			</p>
			<div class="alert alert-info" role="note">
				You can add up to 6 segments. Leave unused editors empty; questions from populated editors will be processed in order.
			</div>

			@php
				$questionSegments = old('question_segments', ['']);
				$questionSegments = is_array($questionSegments) ? array_slice($questionSegments, 0, 6) : [''];
				$questionSegments = $questionSegments ?: [''];
			@endphp

			<form method="POST" action="{{instRoute('questions.upload.store', $courseable->getMorphedId())}}"
				data-segment-url="{{instRoute('questions.upload.segment', $courseable->getMorphedId())}}"
				data-success-url="{{instRoute('questions.index', $courseable->getMorphedId())}}"
				id="segmented-question-form">
				@csrf
				<div class="font-weight-bold mb-3">
					<span>Title: </span> <span>{{$courseable->getName()}}</span>
				</div>

				<div class="alert alert-secondary" id="segment-upload-progress" aria-live="polite" role="status" hidden>
					<strong><span data-sent-count>0</span> sent</strong> ·
					<span data-remaining-count>0</span> remaining
				</div>
				<div id="question-segments" data-max-segments="6">
					@foreach ($questionSegments as $index => $segment)
						@php($segmentError = $errors->first('question_segments.' . $index))
						<div class="question-segment border rounded p-3 mb-3" data-segment>
							<div class="d-flex justify-content-between align-items-center mb-2">
								<label for="question-segment-{{$index + 1}}" class="font-weight-bold mb-0">
									Segment {{$index + 1}}
								</label>
								<button type="button" class="btn btn-outline-danger btn-sm remove-question-segment"
									aria-label="Remove segment {{$index + 1}}"
									{{$index === 0 ? 'disabled' : ''}}>
									<i class="fa fa-trash" aria-hidden="true"></i>
									<span class="ml-1">Remove</span>
								</button>
							</div>
							<textarea name="question_segments[]" id="question-segment-{{$index + 1}}"
								rows="18" class="form-control segmented-question-editor"
								aria-label="Question segment {{$index + 1}}"
								aria-describedby="question-segment-error-{{$index + 1}}">{{ $segment }}</textarea>
							<div class="d-flex justify-content-between mt-1">
								<small class="text-muted" data-character-count aria-live="polite">
									0/2,500 characters
								</small>
							</div>
							<div class="small mt-2" data-segment-status aria-live="polite"></div>
							<div id="question-segment-error-{{$index + 1}}"
								class="text-danger small mt-1" data-character-error role="alert" {{$segmentError ? '' : 'hidden'}}>
								{{$segmentError ?: 'This segment has exceeded the 2,500-character limit. Click “Add segment” to continue entering the remaining content in another segment.'}}
							</div>
						</div>
					@endforeach
				</div>

				<div class="d-flex flex-wrap align-items-center justify-content-between mt-3">
					<button type="button" id="add-question-segment" class="btn btn-outline-primary mb-2">
						<i class="fa fa-plus" aria-hidden="true"></i>
						<span class="ml-1">Add segment</span>
						<span id="question-segment-count" class="ml-1">({{count($questionSegments)}}/6)</span>
					</button>
					<button type="submit" class="btn btn-primary mb-2" id="submit-segmented-questions">
						<i class="fa fa-cloud-upload" aria-hidden="true"></i>
						<span class="ml-1">Process and upload segments</span>
					</button>
				</div>
			</form>
		</div>
	</div>
	
	<div class="justify-content-center">
    	<div class="tile">
			<div class="tile-title">Upload Session Questions</div>
			<div>
				Before uploading, make sure the excel questions are arranged along the following columns
				<div><b>A</b> => Questions No</div>
				<div><b>B</b> => Questions</div>
				<div><b>C</b> => Option A</div>
				<div><b>D</b> => Option B</div>
				<div><b>E</b> => Option C</div>
				<div><b>F</b> => Option D</div>
				<div><b>G</b> => Option E</div>
				<div><b>H</b> => Answer</div>
				<br>
				And the first entry should be on row 2
				<br>
				<small><i>Note: Formulars and formatted content may not appear as expected</i></small>
				<hr>
				You can also upload a Word or text document (DOC, DOCX or TXT). The system will send it to AI to extract
				questions, options, and explanations, and convert them into HTML that can be saved in the questions database.
			</div>
			<hr>
			<form method="POST" action="{{instRoute('questions.upload.store', $courseable->getMorphedId())}}"
				enctype="multipart/form-data" >
				@include('common.form_message')
				@csrf
				<div><b>Title: </b> <span>{{$courseable->getName()}}</span></div>
				{{-- <div class="mt-1"><b>Session: </b> <span>{{$courseable->session}}</span></div> --}}
				<br>
				<div class="form-group">
					<label for="" >Question Content</label><br />
					<input type="file" class="form-control" name="file" value=""
						accept=".csv,.xls,.xlsx,.doc,.docx,.txt" />
				</div>
				<br>
				<div class="form-group">
					<input type="submit" name="add" style="width: 60%; margin: auto;" 
							onclick="return confirm('Are you sure?')"
							class="btn btn-primary btn-block" value="{{'Upload'}}">
					<div class="clearfix"></div>
				</div>
			</form>
		</div>
	</div>

</div>

@include('common._tinymce', ['autoInit' => false])

<script>
	(function () {
		var form = document.getElementById('segmented-question-form');
		var segmentsContainer = document.getElementById('question-segments');
		var addButton = document.getElementById('add-question-segment');
		var countLabel = document.getElementById('question-segment-count');
		var submitButton = document.getElementById('submit-segmented-questions');
		var maxSegments = Number(segmentsContainer?.dataset.maxSegments || 6);
		var maxCharacters = 2500;
		var isProcessing = false;
		var editorHeight = 460;
		var nextSegmentId = segmentsContainer?.querySelectorAll('[data-segment]').length + 1 || 1;

		if (!form || !segmentsContainer || !addButton || !countLabel) {
			return;
		}

		function getSegmentCards() {
			return Array.from(segmentsContainer.querySelectorAll('[data-segment]'));
		}

		function countCharacters(value) {
			var container = document.createElement('div');
			container.innerHTML = value || '';
			var text = container.textContent || container.innerText || '';

			return Array.from(text).length;
		}

		function updateCharacterCount(textarea, value) {
			if (!textarea) {
				return false;
			}

			var segmentCard = textarea.closest('[data-segment]');
			var countElement = segmentCard?.querySelector('[data-character-count]');
			var errorElement = segmentCard?.querySelector('[data-character-error]');
			var characterCount = countCharacters(value ?? textarea.value);
			var isOverLimit = characterCount > maxCharacters;

			if (countElement) {
				countElement.textContent = characterCount.toLocaleString() + '/' + maxCharacters.toLocaleString() + ' characters';
				countElement.classList.toggle('text-danger', isOverLimit);
				countElement.classList.toggle('text-muted', !isOverLimit);
			}
			if (errorElement) {
				errorElement.hidden = !isOverLimit;
			}

			textarea.setAttribute('aria-invalid', isOverLimit ? 'true' : 'false');

			return isOverLimit;
		}

		function initEditor(textarea) {
			if (!textarea) {
				return;
			}

			textarea.addEventListener('input', function () {
				updateCharacterCount(textarea);
			});
			updateCharacterCount(textarea);

			if (!window.initTinymce) {
				return;
			}

			window.initTinymce('#' + textarea.id, {
				height: editorHeight,
				menubar: true,
				content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
				setup: function (editor) {
					editor.on('init', function () {
						updateCharacterCount(textarea, editor.getContent({format: 'text'}));
					});
					editor.on('input change keyup undo redo', function () {
						updateCharacterCount(textarea, editor.getContent({format: 'text'}));
					});
				}
			});
		}

		function updateControls() {
			var segmentCards = getSegmentCards();
			var count = segmentCards.length;
			countLabel.textContent = '(' + count + '/' + maxSegments + ')';
			addButton.disabled = count >= maxSegments;
			segmentCards.forEach(function (card, index) {
				var label = card.querySelector('label');
				var removeButton = card.querySelector('.remove-question-segment');
				var textarea = card.querySelector('textarea');
				var segmentNumber = index + 1;

				if (label) {
					label.textContent = 'Segment ' + segmentNumber;
					if (textarea) {
						label.setAttribute('for', textarea.id);
					}
				}
				if (removeButton) {
					removeButton.disabled = count === 1;
					removeButton.setAttribute('aria-label', 'Remove segment ' + segmentNumber);
				}
				if (textarea) {
					textarea.setAttribute('aria-label', 'Question segment ' + segmentNumber);
					textarea.setAttribute('aria-describedby', 'question-segment-error-' + segmentNumber);
					var characterError = card.querySelector('[data-character-error]');
					if (characterError) {
						characterError.id = 'question-segment-error-' + segmentNumber;
					}
				}
			});
		}

		function getEditorContent(textarea) {
			var editor = textarea && window.tinymce ? window.tinymce.get(textarea.id) : null;
			return editor ? editor.getContent() : (textarea?.value || '');
		}

		function updateUploadProgress(sent, total) {
			var progress = document.getElementById('segment-upload-progress');
			if (!progress) return;
			progress.hidden = false;
			progress.querySelector('[data-sent-count]').textContent = String(sent);
			progress.querySelector('[data-remaining-count]').textContent = String(total - sent);
		}

		function createIdempotencyKey() {
			if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (character) {
				var random = Math.random() * 16 | 0;
				return (character === 'x' ? random : (random & 3 | 8)).toString(16);
			});
		}

		function addSegment() {
			var segmentCards = getSegmentCards();
			if (segmentCards.length >= maxSegments) {
				return;
			}

			var segmentNumber = segmentCards.length + 1;
			var segmentId = nextSegmentId++;
			var card = document.createElement('div');
			card.className = 'question-segment border rounded p-3 mb-3';
			card.setAttribute('data-segment', '');
			card.innerHTML =
				'<div class="d-flex justify-content-between align-items-center mb-2">' +
				'<label for="question-segment-' + segmentId + '" class="font-weight-bold mb-0">Segment ' + segmentNumber + '</label>' +
					'<button type="button" class="btn btn-outline-danger btn-sm remove-question-segment" aria-label="Remove segment ' + segmentNumber + '">' +
						'<i class="fa fa-trash" aria-hidden="true"></i><span class="ml-1">Remove</span>' +
					'</button>' +
				'</div>' +
				'<textarea name="question_segments[]" id="question-segment-' + segmentId + '" rows="18" class="form-control segmented-question-editor" aria-label="Question segment ' + segmentNumber + '" aria-describedby="question-segment-error-' + segmentNumber + '"></textarea>' +
				'<div class="d-flex justify-content-between mt-1"><small class="text-muted" data-character-count aria-live="polite">0/2,500 characters</small></div>' +
				'<div class="small mt-2" data-segment-status aria-live="polite"></div>' +
				'<div id="question-segment-error-' + segmentNumber + '" class="text-danger small mt-1" data-character-error role="alert" hidden>This segment has exceeded the 2,500-character limit. Click “Add segment” to continue entering the remaining content in another segment.</div>';

			segmentsContainer.appendChild(card);
			initEditor(card.querySelector('textarea'));
			updateControls();
			card.scrollIntoView({behavior: 'smooth', block: 'center'});
		}

		segmentsContainer.addEventListener('click', function (event) {
			var removeButton = event.target.closest('.remove-question-segment');
			if (!removeButton || removeButton.disabled) {
				return;
			}

			var card = removeButton.closest('[data-segment]');
			var textarea = card?.querySelector('textarea');
			if (textarea && window.tinymce) {
				var editor = window.tinymce.get(textarea.id);
				if (editor) {
					editor.remove();
				}
			}
			card?.remove();
			updateControls();
		});

		addButton.addEventListener('click', addSegment);

		form.addEventListener('submit', async function (event) {
			event.preventDefault();
			if (isProcessing) return;
			if (window.tinymce) {
				window.tinymce.triggerSave();
			}
			var overLimitCard = getSegmentCards().find(function (card) {
				var textarea = card.querySelector('textarea');
				var editor = textarea && window.tinymce ? window.tinymce.get(textarea.id) : null;
				return updateCharacterCount(
					textarea,
					editor ? editor.getContent({format: 'text'}) : textarea?.value
				);
			});
			if (overLimitCard) {
				event.preventDefault();
				overLimitCard.scrollIntoView({behavior: 'smooth', block: 'center'});
				return;
			}
			var populatedCards = getSegmentCards().filter(function (card) {
				var textarea = card.querySelector('textarea');
				var content = getEditorContent(textarea);
				return textarea && (content.replace(/<[^>]*>/g, '').trim() !== '' || /<img\b/i.test(content));
			});
			if (!populatedCards.length) {
				window.alert('Enter question content in at least one segment before submitting.');
				return;
			}
			isProcessing = true;
			var sentCount = populatedCards.filter(function (card) { return card.dataset.sent === 'true'; }).length;
			updateUploadProgress(sentCount, populatedCards.length);
			addButton.disabled = true;
			getSegmentCards().forEach(function (card) {
				var textarea = card.querySelector('textarea');
				var remove = card.querySelector('.remove-question-segment');
				if (textarea) textarea.disabled = true;
				if (remove) remove.disabled = true;
			});
			submitButton.disabled = true;
			submitButton.querySelector('span').innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Sending segments...';

			try {
				for (var index = 0; index < populatedCards.length; index++) {
					var card = populatedCards[index];
					if (card.dataset.sent === 'true') continue;
					var textarea = card.querySelector('textarea');
					var status = card.querySelector('[data-segment-status]');
					var segmentNumber = getSegmentCards().indexOf(card) + 1;
					var idempotencyKey = card.dataset.idempotencyKey || createIdempotencyKey();
					card.dataset.idempotencyKey = idempotencyKey;
					status.className = 'small mt-2 text-primary';
					status.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Sending segment ' + segmentNumber + '...';
					var body = new FormData();
					body.append('segment', new File([getEditorContent(textarea)], 'segment-' + segmentNumber + '.txt', {type: 'text/plain;charset=UTF-8'}));
					body.append('idempotency_key', idempotencyKey);
					body.append('_token', form.querySelector('input[name="_token"]').value);
					var response = await fetch(form.dataset.segmentUrl, {
						method: 'POST',
						body: body,
						headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
					});
					var result = await response.json().catch(function () { return {}; });
					if (!response.ok || !result.success) {
						throw new Error(result.message || result.errors?.segment?.[0] || 'Segment ' + segmentNumber + ' could not be processed.');
					}
					card.dataset.sent = 'true';
					sentCount++;
					status.className = 'small mt-2 text-success';
					status.innerHTML = '<i class="fa fa-check-circle" aria-hidden="true"></i> Segment ' + segmentNumber + ' sent (' + result.questions_count + ' questions added).';
					updateUploadProgress(sentCount, populatedCards.length);
				}
				window.location.assign(form.dataset.successUrl);
			} catch (error) {
				if (typeof card !== 'undefined' && card.dataset.sent !== 'true') {
					var failedStatus = card.querySelector('[data-segment-status]');
					if (failedStatus) {
						failedStatus.className = 'small mt-2 text-danger';
						failedStatus.textContent = 'This segment was not confirmed. Retry to continue safely.';
					}
				}
				window.alert(error.message || 'Unable to process the segments. Please try again.');
				getSegmentCards().forEach(function (card) {
					var textarea = card.querySelector('textarea');
					var remove = card.querySelector('.remove-question-segment');
					if (textarea) textarea.disabled = card.dataset.sent === 'true';
					if (remove) remove.disabled = card.dataset.sent === 'true' || getSegmentCards().length === 1;
				});
				addButton.disabled = getSegmentCards().length >= maxSegments;
				submitButton.disabled = false;
				submitButton.querySelector('span').textContent = 'Retry remaining segments';
				isProcessing = false;
			}
		});

		getSegmentCards().forEach(function (card) {
			initEditor(card.querySelector('textarea'));
		});
		updateControls();
	})();
</script>

@endsection
