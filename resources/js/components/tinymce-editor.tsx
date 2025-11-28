import React from 'react';
import { Editor, IAllProps } from '@tinymce/tinymce-react';

interface Props {
  title?: string;
}

export default function TinyMceEditor({ ...props }: IAllProps) {
  return (
    <Editor
      {...props}
      tinymceScriptSrc={
        `${window.location.origin}/lib/tinymce6/tinymce.min.js`
        // 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.9.1/tinymce.min.js'
      }
      init={{
        height: 300,
        menubar: true,
        plugins: 'image table code charmap lists',
        // plugins: [
        //   'advlist autolink lists link image charmap print preview anchor',
        //   'searchreplace visualblocks code fullscreen',
        //   'insertdatetime media table paste code help wordcount',
        // ],
        toolbar:
          'bullist | numlist | tiny_mce_wiris_formulaEditor | tiny_mce_wiris_formulaEditorChemistry | undo redo | link image | code | bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull | formatselect | backcolor | alignleft aligncenter  alignright alignjustify |  outdent indent |  removeformat',
        content_style:
          'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        charmap_append: [
          [0x2600, 'sun'],
          [0x20a6, 'naira'],
          [0x2601, 'cloud'],
        ],
        external_plugins: {
          tiny_mce_wiris: `${window.location.origin}/lib/@wiris/mathtype-tinymce6/plugin.min.js`,
          // 'https://cdn.jsdelivr.net/npm/@wiris/mathtype-tinymce7@8.13.2/plugin.min.js',
          // 'node_modules/@wiris/mathtype-tinymce7/plugin.min.js',
        },
      }}
      // value={webForm.data.content}
      // onEditorChange={(content: string) => webForm.setValue('content', content)}
    />
  );
}
