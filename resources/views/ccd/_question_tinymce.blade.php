<script src="https://cdn.tiny.cloud/1/x5fywb7rhiv5vwkhx145opfx4rsh70ytqkiq2mizrg73qwc2/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script type="text/javascript">

function initTinymce() {
    	
	tinymce.init({
		selector:'.useEditor',
		valid_elements : '*[*]',
		browser_spellcheck : true,
    plugins: 'image code charmap',

    charmap_append: [
        [0x2600, 'sun'],
        [0x20A6, 'naira'],
        [0x2601, 'cloud']
    ],

    // urlconverter_callback: 'myCustomURLConverter',
    // convert_urls: false, 
    
    // document_base_url: '{{$imagePath}}',

    toolbar: 'tiny_mce_wiris_formulaEditor | tiny_mce_wiris_formulaEditorChemistry | undo redo | link image | code | bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,',
    
    // enable title field in the Image dialog
    image_title: true, 
    
    // enable automatic uploads of images represented by blob or data URIs
    automatic_uploads: true,

    // URL of our upload handler (for more details check: https://www.tinymce.com/docs/configure/file-image-upload/#images_upload_url)
    // images_upload_url: 'postAcceptor.php',
    // here we add custom filepicker only to Image dialog
    file_picker_types: 'image', 

    // and here's our custom image picker
    file_picker_callback: function(cb, value, meta) {
        var input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        
        // Note: In modern browsers input[type="file"] is functional without 
        // even adding it to the DOM, but that might not be the case in some older
        // or quirky browsers like IE, so you might want to add it to the DOM
        // just in case, and visually hide it. And do not forget do remove it
        // once you do not need it anymore.
        
        input.onchange = function() {
          var file = this.files[0];
          
          var reader = new FileReader();
          reader.onload = function () {
            // Note: Now we need to register the blob in TinyMCEs image blob
            // registry. In the next release this part hopefully won't be
            // necessary, as we are looking to handle it internally.
            var id = 'blobid' + (new Date()).getTime();
            var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
            var base64 = reader.result.split(',')[1];
            var blobInfo = blobCache.create(id, file, base64);
            blobCache.add(blobInfo);
        
            // call the callback and populate the Title field with the file name
            cb(blobInfo.blobUri(), { title: file.name });
          };
          reader.readAsDataURL(file);
        };
        
        input.click();
    },

     // without images_upload_url set, Upload tab won't show up
    images_upload_url: '{{$uploadURL}}',
        
    images_upload_handler: function (blobInfo, progress) {
      
      return new Promise((resolve, reject) => {
        var xhr, formData;

        xhr = new XMLHttpRequest();
        xhr.withCredentials = false;
        xhr.open('POST', "{{$uploadURL}}");

        xhr.upload.onprogress = (e) => {
          progress(e.loaded/e.total * 100)
        }

        xhr.onload = function() {
          var json;
          
          if (xhr.status != 200) {
            reject('HTTP Error: ' + xhr.status);
            return;
          }

          json = JSON.parse(xhr.responseText);

          if (!json || typeof json.location != 'string') {
            reject('Invalid JSON: ' + xhr.responseText);
            return;
          }

          resolve(json.location);
        };

        formData = new FormData();
        
        formData.set('file', blobInfo.blob(), blobInfo.filename());

        xhr.send(formData);
      });

      xhr.onerror = (e) => {
        reject('Image upload failed');
      }
    }
        
	});
	
	tinyMCE.triggerSave();
}

$(function() {
  if(window.handleImages){
    handleImages();
  }
	initTinymce();
});
</script>