/*
 * Builder Index controller Imports entity controller
 */
+function ($) { "use strict";

    if ($.oc.builder === undefined) {
        $.oc.builder = {};
    }

    if ($.oc.builder.entityControllers === undefined) {
        $.oc.builder.entityControllers = {};
    }

    var Base = $.oc.builder.entityControllers.base,
        BaseProto = Base.prototype;

    var Imports = function(indexController) {
        Base.call(this, 'imports', indexController);
    }

    Imports.prototype = Object.create(BaseProto);
    Imports.prototype.constructor = Imports;

    // PUBLIC METHODS
    // ============================

    Imports.prototype.cmdOpenImports = function(ev) {
        var currentPlugin = this.getSelectedPlugin();

        if (!currentPlugin) {
            alert('Please select a plugin first');
            return;
        }

        this.indexController.openOrLoadMasterTab($(ev.target), 'onImportsOpen', this.makeTabId(currentPlugin));
    }

    Imports.prototype.cmdSaveImports = function(ev) {
        var $target = $(ev.currentTarget);
        $.oc.confirm($target.data('confirm'), this.proxy(this.saveImportsConfirmed));
    }

    Imports.prototype.saveImportsConfirmed = function(ev) {
        var $masterTabPane = this.getMasterTabsActivePane(),
            $form = $masterTabPane.find('form');

        $form.request('onImportsSave', {
            data: {}
        }).done(
            this.proxy(this.saveImportsDone)
        );
    }

    Imports.prototype.cmdMigrateDatabase = function(ev) {
        var $target = $(ev.currentTarget);
        $target.request('onMigrateDatabase');
    }

    Imports.prototype.cmdAddBlueprintItem = function(ev) {
        // $.oc.builder.blueprintbuilder.controller.addBlueprintItem(ev)
    }

    Imports.prototype.cmdRemoveBlueprintItem = function(ev) {
        // $.oc.builder.blueprintbuilder.controller.removeBlueprint(ev)
    }

    // INTERNAL METHODS
    // ============================

    Imports.prototype.saveImportsDone = function(data) {
        if (data['builderResponseData'] === undefined) {
            throw new Error('Invalid response data');
        }

        this.hideInspector();
        $('#blueprintList').html('');

        var $masterTabPane = this.getMasterTabsActivePane();
        this.getIndexController().unchangeTab($masterTabPane);
    }

    Imports.prototype.hideInspector = function() {
        var $container = $('.blueprint-container.inspector-open:first');

        if ($container.length) {
            var $inspectorContainer = this.findInspectorContainer($container);
            $.oc.foundation.controlUtils.disposeControls($inspectorContainer.get(0));
        }
    }

    Imports.prototype.findInspectorContainer = function($element) {
        var $containerRoot = $element.closest('[data-inspector-container]')
        return $containerRoot.find('.inspector-container')
    }

    // REGISTRATION
    // ============================

    $.oc.builder.entityControllers.imports = Imports;

}(window.jQuery);
