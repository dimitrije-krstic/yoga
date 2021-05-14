// TEXT EDITOR FOR POSTS
tinymce.init({
    selector: '.postContent',
    placeholder: 'Post content goes here...',
    plugins: 'lists image',
    toolbar: 'bold italic bullist | image | removeformat ',
    toolbar_mode: 'floating',
    width: 800,
    height: 600,
    relative_urls: false,
    automatic_uploads: false,
    image_caption: false,
    image_description: false,
    image_dimensions: false,
    resize: 'both',
    elementpath: false,
    menubar: false,
    skin: 'bootstrap',
    content_style: "p{ font-size:18px; } li{ font-size:18px; } img {margin-left: auto; margin-right: auto; max-width: 100%; display: block; border-radius: 25px;}",
    image_class_list: [
        {title: 'default', value: 'postContentImage'}
    ],
    extended_valid_elements : "img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|loading=lazy]"
});

// TEXT EDITOR FOR VIDEO POSTS
tinymce.init({
    selector: '.videoPostContent',
    placeholder: 'Content goes here...',
    plugins: 'lists',
    toolbar: 'bold italic bullist | removeformat ',
    toolbar_mode: 'floating',
    width: 800,
    height: 600,
    resize: 'both',
    elementpath: false,
    menubar: false,
    skin: 'bootstrap',
    content_style: "p{ font-size:18px; } li{ font-size:18px; }"
});

// POST IMAGE UPLOAD
    $('.upload-image').on('change', function(event) {
        $('#imageUploadButton').toggle();
        $('#imageUploadButtonSpinner').toggle();

        $('#submit-image-upload').click();
    });