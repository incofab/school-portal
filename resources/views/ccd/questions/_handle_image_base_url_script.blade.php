<?php
$questionImgUrl =
  config('filesystems.disks.s3_public.url') .
  '/' .
  $institution->folder(
    \App\Enums\S3Folder::CCD,
    "{$courseSession->course_id}/{$courseSession->id}/"
  ); ?>
<script>
	function getImageBaseUrl(src, alt) {
		let filename = getUrlLastPath(src) ?? '';
		if(!isValidImage(filename)) {
			filename = alt;
		}
		return "{{$questionImgUrl}}"+filename;
	}
	
	function getUrlLastPath(urlPath) {
		if(!urlPath){
			return '';
		}
		const lastPart = urlPath.split('/').pop();
		if(isValidImage(lastPart)) {
			return lastPart;
		}
		const prefix = 'filename=';
		const startPoint = urlPath.substring(urlPath.lastIndexOf(prefix));
		// console.log(filename, ' | | ', startPoint.substring(prefix.length, startPoint.indexOf("&")));
		const amperSandIndex = startPoint.indexOf("&");
		return getUrlLastPath(startPoint.substring(prefix.length, amperSandIndex == -1 ? undefined : amperSandIndex));
	}

	function isValidImage(filename){
		if(filename.length < 4){
			return false;
		}
		if(!['.jpg', '.gif', '.png', 'jpeg'].includes(filename.substr(-4))){
			return false;
		}
		return true;
	}
</script>