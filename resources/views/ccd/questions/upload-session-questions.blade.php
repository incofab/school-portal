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
				id="segmented-question-form">
				@csrf
				<div class="font-weight-bold mb-3">
					<span>Title: </span> <span>{{$courseable->getName()}}</span>
				</div>

				<div id="question-segments" data-max-segments="6">
					@foreach ($questionSegments as $index => $segment)
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
								aria-label="Question segment {{$index + 1}}">{{ $segment }}</textarea>
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
		var editorHeight = 460;
		var nextSegmentId = segmentsContainer?.querySelectorAll('[data-segment]').length + 1 || 1;

		if (!form || !segmentsContainer || !addButton || !countLabel) {
			return;
		}

		function getSegmentCards() {
			return Array.from(segmentsContainer.querySelectorAll('[data-segment]'));
		}

		function initEditor(textarea) {
			if (!textarea || !window.initTinymce) {
				return;
			}

			window.initTinymce('#' + textarea.id, {
				height: editorHeight,
				menubar: true,
				content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
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
				}
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
				'<textarea name="question_segments[]" id="question-segment-' + segmentId + '" rows="18" class="form-control segmented-question-editor" aria-label="Question segment ' + segmentNumber + '"></textarea>';

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

		form.addEventListener('submit', function (event) {
			if (window.tinymce) {
				window.tinymce.triggerSave();
			}
			var hasContent = getSegmentCards().some(function (card) {
				var textarea = card.querySelector('textarea');
				return textarea && (textarea.value.replace(/<[^>]*>/g, '').trim() !== '' || /<img\b/i.test(textarea.value));
			});
			if (!hasContent) {
				event.preventDefault();
				window.alert('Enter question content in at least one segment before submitting.');
				return;
			}
			if (!window.confirm('Process and upload the populated question segments?')) {
				event.preventDefault();
				return;
			}
			if (submitButton) {
				submitButton.disabled = true;
				submitButton.querySelector('span').textContent = 'Processing segments...';
			}
		});

		getSegmentCards().forEach(function (card) {
			initEditor(card.querySelector('textarea'));
		});
		updateControls();
	})();
</script>

@endsection
