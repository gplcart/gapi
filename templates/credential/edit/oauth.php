<?php
/**
 * @package CLI
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */
?>
<form method="post" class="form-horizontal">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <div class="form-group<?php echo $this->error('name', ' has-error'); ?>">
    <label class="col-md-2 control-label"><?php echo $this->text('Name'); ?></label>
    <div class="col-md-4">
      <input name="credential[name]" class="form-control" value="<?php echo isset($credential['name']) ? $credential['name'] : ''; ?>">
      <div class="help-block">
          <?php echo $this->error('name'); ?>
        <div class="text-muted">
            <?php echo $this->text('Name for administrators'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="form-group<?php echo $this->error('data.id', ' has-error'); ?>">
    <label class="col-md-2 control-label"><?php echo $this->text('Client ID'); ?></label>
    <div class="col-md-4">
      <input name="credential[data][id]" class="form-control" value="<?php echo isset($credential['data']['id']) ? $credential['data']['id'] : ''; ?>">
      <div class="help-block">
          <?php echo $this->error('data.id'); ?>
        <div class="text-muted">
            <?php echo $this->text('Client ID you got from https://console.developers.google.com/apis/credentials'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="form-group<?php echo $this->error('data.secret', ' has-error'); ?>">
    <label class="col-md-2 control-label"><?php echo $this->text('Client secret'); ?></label>
    <div class="col-md-4">
      <input name="credential[data][secret]" class="form-control" value="<?php echo isset($credential['data']['secret']) ? $credential['data']['secret'] : ''; ?>">
      <div class="help-block">
          <?php echo $this->error('data.secret'); ?>
        <div class="text-muted">
            <?php echo $this->text('Client secret you got from https://console.developers.google.com/apis/credentials'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="form-group">
    <div class="col-md-4 col-md-offset-2">
      <div class="btn-toolbar">
          <?php if(isset($credential['credential_id']) && $this->access('module_gapi_credential_delete')) { ?>
            <button class="btn btn-danger" name="delete" value="1" onclick="return confirm('<?php echo $this->text('Are you sure? It cannot be undone!'); ?>');">
                <?php echo $this->text('Delete'); ?>
            </button>
          <?php } ?>
          <a href="<?php echo $this->url('admin/report/gapi'); ?>" class="btn btn-default"><?php echo $this->text('Cancel'); ?></a>
          <?php if($this->access('module_gapi_credential_add') || $this->access('module_gapi_credential_edit')) { ?>
          <button class="btn btn-default save" name="save" value="1"><?php echo $this->text("Save"); ?></button>
          <?php } ?>
      </div>
    </div>
  </div>
</form>