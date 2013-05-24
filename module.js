M.mod_instantquiz = M.mod_instantquiz || {};
M.mod_instantquiz.init_templatechooser = function(Y, params) {
    if (params && params.formid) {
        var updatebut = Y.one('#'+params.formid+' #id_updatetemplate');
        var templateselect = Y.one('#'+params.formid+' #id_template');
        if (updatebut && templateselect) {
            templateselect.on('change', function() {
                updatebut.simulate('click');
            });
        }
    }
}