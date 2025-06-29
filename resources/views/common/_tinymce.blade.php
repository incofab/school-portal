<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.9.1/tinymce.min.js" integrity="sha512-09JpfVm/UE1F4k8kcVUooRJAxVMSfw/NIslGlWE/FGXb2uRO1Nt4BXAJ3LxPqNbO3Hccdu46qaBPp9wVpWAVhA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
{{-- <script src="{{asset('js/lib/tinymce6/tinymce.min.js')}}" referrerpolicy="origin"></script> --}}

<script type="text/javascript">

function initTinymce() {
    	
	tinymce.init({
		selector:'.useEditor',
		valid_elements : '*[*]',
		browser_spellcheck : true,
    plugins: 'image table code charmap lists',

    charmap_append: [
        [0x2600, 'sun'],
        [0x20A6, 'naira'],
        [0x2601, 'cloud']
    ],

    external_plugins: {
      tiny_mce_wiris: 'https://cdn.jsdelivr.net/npm/@wiris/mathtype-tinymce7@8.13.2/plugin.min.js',
      // tiny_mce_wiris: '/js/lib/@wiris/mathtype-tinymce7/plugin.min.js',
    },  
    draggable_modal: true,

    toolbar: 'bullist | numlist | tiny_mce_wiris_formulaEditor | tiny_mce_wiris_formulaEditorChemistry | undo redo | link image | code | bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,',
    
    // enable title field in the Image dialog
    image_title: true, 
    
    // enable automatic uploads of images represented by blob or data URIs
    automatic_uploads: true,

    // URL of our upload handler (for more details check: https://www.tinymce.com/docs/configure/file-image-upload/#images_upload_url)
    // images_upload_url: 'postAcceptor.php',
    // here we add custom filepicker only to Image dialog
    file_picker_types: 'image', 
    
	});
	
	tinyMCE.triggerSave();
}

$(function() {
	initTinymce();
});
</script>