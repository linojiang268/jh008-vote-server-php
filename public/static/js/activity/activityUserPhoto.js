(function() {
    var photos = K.photos,
        server = K.server,
        DialogUi = K.dialogUi,
        util = K.util;

    var PhotoApplication = K.PhotoApplication;

    var _token = $('input[name="_token"]').val();

    var page = {
        initialize: function() {
            var photoApplication = new PhotoApplication($('#photosManager'), {
                _token: _token,
                btns: ['passBtn', 'batchBtn', 'tipBtn', 'deleteBtn', 'finishBtn'],
                type: 'user'
            })
        }
    }

    page.initialize();

})()