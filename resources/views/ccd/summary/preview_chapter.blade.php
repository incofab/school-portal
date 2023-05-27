
@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg ">
	
	 @include('ccd.common.message')
		
	 	<div class="panel-heading templatemo-position-relative">
	 	
	 		<h2>{{ $courseName }} Summary for Chapter <?= $previewData[CHAPTER_NO] ?></h2>
	 		<br />
			<div>
				<label for="" >Title:</label>
				<span><?= $previewData[TITLE] ?></span>
			</div>
			<div>
				<label for="" >Description:</label>
				<span><?= $previewData[DESCRIPTION] ?></span>
			</div>
			<br />	 		
			<div >
				<label for="" >Chapter Summary</label>
				<div class="text-body">
					<?= $previewData[SUMMARY] ?>
					<div class="clearfix"></div>
				</div>
			</div> 		 		
	 	</div>
		
		<a class="templatemo-blue-button width-20" href="<?= getAddr('ccd_edit_chapter', 
							[$courseId, $previewData[TABLE_ID]]) ?>">Edit</a>


		
	</div>

@stop