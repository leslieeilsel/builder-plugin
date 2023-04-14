<div class="layout-absolute builder-tailor-builder-area">
    <div class="control-scrollpad" data-control="scrollpad">
        <div class="scroll-wrapper">
            <ul class="tailor-blueprint-list" id="blueprintList">
                <?php foreach ($model->blueprints as $blueprintUuid => $blueprintConfig): ?>
                    <?= $this->makePartial('blueprint', [
                        'blueprintUuid' => $blueprintUuid,
                        'blueprintConfig' => $blueprintConfig
                    ]) ?>
                <?php endforeach ?>

                <?= $this->makePartial('blueprint', [
                    'blueprintUuid' => 'edcd102e-0525-4e4d-b07e-633ae6c18db6',
                    'blueprintConfig' => []
                ]) ?>
            </ul>
            <div class="add-blueprint-button">
                <a href="javascript:;"
                    data-hotkey="ctrl+i, cmd+i"
                    <?php /*data-builder-command="imports:cmdAddBlueprintItem"*/ ?>
                    data-control="popup"
                    data-handler="<?= $this->getEventHandler('onShowSelectBlueprintForm') ?>"
                >
                    <i class="icon-plus-circle"></i>
                    <span class="title"><?= __("Add Blueprint") ?></span>
                </a>
            </div>
        </div>
    </div>
</div>
