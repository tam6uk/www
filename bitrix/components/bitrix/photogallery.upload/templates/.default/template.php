<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
* Bitrix vars
*
* @var array $arParams
* @var array $arResult
* @var string $templateFolder
* @var CBitrixComponentTemplate $this
* @var CMain $APPLICATION
* @var CUser $USER
*/
CUtil::InitJSCore("core", "ajax");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/photogallery/templates/.default/script.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/photogallery.interface/templates/.default/script.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/search.tags.input/templates/.default/script.js");

if (!$this->__component->__parent || strpos($this->__component->__parent->__name, "photogallery") === false)
{
	$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/photogallery/templates/.default/style.css");
	$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/photogallery/templates/.default/themes/gray/style.css");
}

$APPLICATION->AddHeadString('<link href="/bitrix/components/bitrix/search.tags.input/templates/.default/style.css" type="text/css" rel="stylesheet" />', true);

/*************************************************************************
	Processing of received parameters
*************************************************************************/
$arParams["WATERMARK"] = ($arParams["WATERMARK"] == "N" ? "N" : "Y");
$arParams["TEMPLATE"] = ($arParams["USE_LIGHT_TEMPLATE"] == "Y" ? "LIGHT-APPLET" : "APPLET");
$arParams["SHOW_WATERMARK"] = ($arParams["SHOW_WATERMARK"] == "N" ? "N" : "Y");
if ($arParams["USE_WATERMARK"] != "Y" || $arParams["WATERMARK"] != "Y")
	$arParams["SHOW_WATERMARK"] = "N";

$arParams["JPEG_QUALITY1"] = intVal($arParams["JPEG_QUALITY1"]) > 0 ? intVal($arParams["JPEG_QUALITY1"]) : 80;
$arParams["JPEG_QUALITY2"] = intVal($arParams["JPEG_QUALITY2"]) > 0 ? intVal($arParams["JPEG_QUALITY2"]) : 90;
$arParams["JPEG_QUALITY"] = intVal($arParams["JPEG_QUALITY"]) > 0 ? intVal($arParams["JPEG_QUALITY"]) : 90;
$arParams["USER_SETTINGS"] = (is_array($arParams["USER_SETTINGS"]) ? $arParams["USER_SETTINGS"] : array());
$arParams["id"] = getImageUploaderId("Uploader");
/********************************************************************
	/Processing of received parameters
********************************************************************/

/********************************************************************
				Default values
********************************************************************/
$arWatermarkPos = array("TopLeft", "TopCenter", "TopRight", "CenterLeft", "Center", "CenterRight", "BottomLeft", "BottomCenter", "BottomRight");
$arWatermarkDefault = array(
	"additional" => "N",
	"use" => "Y",
	"type" => strtolower($arParams["WATERMARK_TYPE"]),
	"copyright" => "N",
	"color" => $arParams["WATERMARK_COLOR"],
	"position" => $arParams["WATERMARK_POSITION"],
	"opacity" => (isset($arParams["WATERMARK_TRANSPARENCY"]) ? intVal($arParams["WATERMARK_TRANSPARENCY"]) : 50),
	"text" => $arParams["WATERMARK_TEXT"],
	"file" => $arParams["WATERMARK_FILE_REL"],
	"fileWidth" => $arParams["WATERMARK_FILE_WIDTH"],
	"fileHeight" => $arParams["WATERMARK_FILE_HEIGHT"]);

$arWatermark = ($arParams["WATERMARK_RULES"] == "ALL" ? $arWatermarkDefault : array_merge($arWatermarkDefault, $arParams["USER_SETTINGS"]));
$arWatermark["additional"] = ($arWatermark["additional"] == "Y");
$arWatermark["use"] = ($arWatermark["use"] == "Y" ? "Y" : "N");
$arWatermark["type"] = (in_array($arWatermark["type"], array("text", "image")) ? $arWatermark["type"] : "text");
$arWatermark["copyright"] = ($arWatermark["copyright"] == "Y" ? "Y" : "N");
$arWatermark["color"] = htmlspecialcharsbx($arWatermark["color"] ?: "#FF0000");
$arWatermark["size"] = (in_array($arWatermark["size"], array("real", "big", "middle", "small")) ? $arWatermark["size"] : "real");
$arWatermark["position"] = (in_array($arWatermark["position"], $arWatermarkPos) ? $arWatermark["position"] : "BottomRight");
$arWatermark["opacity"] = intVal($arWatermark["opacity"] ?: 50);
$arWatermark["text"] = htmlspecialcharsbx($arWatermark["text"]);
$arWatermark["original_size"] = intVal($arWatermark["original_size"]);
$htmlSettings = array();

if($arParams["SHOW_RESIZER"] == "Y")
{
	if ($arParams["ORIGINAL_SIZE"] == 0)
		array_unshift($arParams["SIZES_SHOWN"], array(0, GetMessage("P_ORIGINAL")));
	$htmlSettings["resizer"] =
		"<div class=\"bxiu-settings bxiu-setting-user-resizer\">".
			"<label for=\"photo_resize_size\">".GetMessage("P_RESIZE").":</label>".
			"<select id=\"bxiu_resize_{$arParams["id"]}\">";
			foreach ($arParams['SIZES_SHOWN'] as $size)
				$htmlSettings["resizer"] .= "<option value=\"{$size[0]}\" ".($arWatermark["original_size"] == $size[0] ? " selected" : "").">{$size[1]}</option>";
			$htmlSettings["resizer"] .= "
		</select>
	</div>";
}

if ($arParams["SHOW_PUBLIC"] == "Y")
{
	$htmlSettings["public"] =
		"<div class=\"bxiu-settings bxiu-setting-user-public\">".
			"<input name=\"Public\" id=\"bxiu_public_{$arParams["id"]}\" type=\"checkbox\" value=\"Y\" ".
				($arParams["PUBLIC_BY_DEFAULT"] == "Y" ? ' checked="checked" ' : ""). "/>".
			"<label for=\"bxiu_public_{$arParams["id"]}\">".GetMessage("Public")."</label>".
		"</div>";
}

if ($arParams["SHOW_WATERMARK"] == "Y")
{
	$wm = $arWatermark + array(
			"P_WATERMARK" => GetMessage("P_WATERMARK"),
			"P_WATERMARK_TEXT" => GetMessage("P_WATERMARK_TEXT"),
			"P_WATERMARK_IMG" => GetMessage("P_WATERMARK_IMG"),
			"P_DEL_PREVIEW" => GetMessage("P_DEL_PREVIEW")
		);
	$wm["use"] = ($wm["use"] == "Y" ? "checked" : "");
	$wm["use_image"] = ($arWatermark["type"] == "text" ? "" : "checked");
	$wm["use_text"] = ($arWatermark["type"] == "text" ? "checked" : "");
	$htmlSettings["watermark"] = <<<HTML
<div id="{$arParams["id"]}_watermark_cont" class="bxiu-settings bxiu-setting-watermark">
	<div class="bxiu-watermark-use-cont">
		<input type="checkbox" id="{$arParams["id"]}_use_watermark" value="Y" {$wm["use"]}/>
		<label for="{$arParams["id"]}_use_watermark">{$wm["P_WATERMARK"]}</label>
	</div>
	<div class="bxiu-watermark-cont">
		<div class="bxiu-watermark-type-cont">
			<input type="radio" id="{$arParams["id"]}_wmark_type_text" {$wm["use_text"]} name="wmark_type_radio" value="text" /> <label for="{$arParams["id"]}_wmark_type_text">{$wm["P_WATERMARK_TEXT"]}</label>
			<input type="radio" id="{$arParams["id"]}_wmark_type_img" {$wm["use_image"]} name="wmark_type_radio" value="image" /> <label for="{$arParams["id"]}_wmark_type_img">{$wm["P_WATERMARK_IMG"]}</label>
		</div>
		<div class="bxiu-watermark-image">
			<div id="{$arParams["id"]}_wmark_preview_cont" class="bxiu-watermark-preview">
				<div class="bxiu-watermark-image-preview">
					<img class="bxiu-watermark-image-preview" id="watermark_img_preview{$arParams["id"]}" src="/bitrix/images/1.gif"/>
				</div>
				<div id="{$arParams["id"]}_wmark_preview_del" class="bxiu-file-del" title="{$wm["P_DEL_PREVIEW"]}"></div>
			</div>
			<div id="bxiu_wm_img_iframe_cont{$arParams["id"]}">
				<input name="watermark_img" type="file" size="30" id="bxiu_wm_img{$arParams["id"]}"/>
				<div class="bxiu-watermark-image-but-cont" id="{$arParams["id"]}_img_but_cont"></div>
			</div>
		</div>
		<div class="bxiu-watermark-text">
			<input type="text" id="{$arParams["id"]}_wmark_text" value="{$wm["text"]}" size="25" class="bxiu-watermark-text-inp"/>
			<div class="bxiu-watermark-text-but-cont"  id="{$arParams["id"]}_text_but_cont"></div>
		</div>
	</div>
</div>
HTML;
}
if (empty($htmlSettings))
	$htmlSettings = "";
else
{
	$params = CUtil::PhpToJSObject(array(
		"id" => $arParams["id"],
		"UPLOADER_ID" => $arParams["UPLOADER_ID"],
		"show" => array_keys($htmlSettings),
		"params" => $arWatermark ));
	$htmlSettings = implode("", $htmlSettings);
	$htmlSettings = <<<HTML
<div class="bxiu-add-params">$htmlSettings<div style="clear: both"></div></div>
<script type="text/javascript">
	BX.ready(function(){
		new BX.UploaderSettings({$params});
	})
</script>
HTML;
	$htmlSettings .=
"<script>
	BX.message({
		IUDefaultColor: '".GetMessageJS("P_DEF_COLOR")."',
		IUTopLeft: '".GetMessageJS("P_WATERMARK_POSITION_TL")."',
		IUTopCenter: '".GetMessageJS("P_WATERMARK_POSITION_TC")."',
		IUTopRight: '".GetMessageJS("P_WATERMARK_POSITION_TR")."',
		IUCenterLeft: '".GetMessageJS("P_WATERMARK_POSITION_ML")."',
		IUCenter: '".GetMessageJS("P_WATERMARK_POSITION_MC")."',
		IUCenterRight: '".GetMessageJS("P_WATERMARK_POSITION_MR")."',
		IUBottomLeft: '".GetMessageJS("P_WATERMARK_POSITION_BL")."',
		IUBottomCenter: '".GetMessageJS("P_WATERMARK_POSITION_BC")."',
		IUBottomRight: '".GetMessageJS("P_WATERMARK_POSITION_BR")."',
		IUSizeReal: '".GetMessageJS("P_WATERMARK_SIZE_REAL")."',
		IUSizeBig: '".GetMessageJS("P_WATERMARK_SIZE_BIG")."',
		IUSizeMiddle: '".GetMessageJS("P_WATERMARK_SIZE_MIDDLE")."',
		IUSizeSmall: '".GetMessageJS("P_WATERMARK_SIZE_SMALL")."',
		IUOpacity: '".GetMessageJS("P_OPACITY")."',
		IUPositionTitle: '".GetMessage("P_WATERMARK_POSITION_TITLE")."',
		IUSizeTitle: '".GetMessage("P_WATERMARK_SIZE_TITLE")."',
		IUCopyrightTitleOn: '".(GetMessage("P_WATERMARK_COPYRIGHT").": ".GetMessage("P_WATERMARK_COPYRIGHT_SHOW"))."',
		IUCopyrightTitleOff: '".(GetMessage("P_WATERMARK_COPYRIGHT").": ".GetMessage("P_WATERMARK_COPYRIGHT_HIDE"))."',
		IUDelEntry: '".GetMessageJS("P_DEL_PREVIEW")."',
		IUDelEntryConfirm: '".GetMessageJS("P_DEL_PREVIEW_CONFIRM")."',
		IUSourceFile: '".GetMessageJS("SourceFile")."',
		IUTitle: '".GetMessageJS("Title")."',
		IUTags: '".GetMessageJS("Tags")."',
		IUDescription: '".GetMessageJS("Description")."',
		IUNoPhoto: '".GetMessageJS("NoPhoto")."',
		IUPublic: '".GetMessageJS("Public")."',
		IUErrorNoData: '".GetMessageJS("ErrorNoData", array('#POST_MAX_SIZE#' => $arResult["UPLOAD_MAX_FILE_SIZE_MB"]))."',
		IULargeSizeWarn: '".GetMessageJS("P_LARGE_SIZE_WARN")."',
		IUWrongTypeWarn: '".GetMessageJS("P_NOT_IMAGE_TYPE_WARN")."',
		IUWrongServerResponse: '".GetMessageJS("P_WRONG_SERVER_RESPONSE")."'
	});
</script>";
}


/********************************************************************
				/Default values
********************************************************************/
?>
<?if (!empty($arResult["ERROR_MESSAGE"])):?>
<div id="photo_error_<?=$arParams["UPLOADER_ID"]?>" class="photo-error">
	<?ShowError($arResult["ERROR_MESSAGE"]);?>
</div>
<?endif;

if($arParams["SHOW_MAGIC_QUOTES_NOTICE_ADMIN"])
	echo GetMessage("MAGIC_QUOTES_NOTICE_ADMIN", array("#URL#" => "/bitrix/admin/site_checker.php"))."<br/><br/>";
elseif($arParams["SHOW_MAGIC_QUOTES_NOTICE"])
	echo GetMessage("MAGIC_QUOTES_NOTICE")."<br/><br/>";
/* ************** Select uploader type ************** */
if ($arParams["VIEW_MODE"] != "applet")
{
CJSCore::Init(array("uploader", "canvas"));
$edit = GetMessage("MFU_EDIT");
$turn = GetMessage("MFU_TURN");
$del = GetMessage("MFU_DEL");
$thumb = <<<HTML
<span class="bxu-item-block">
	<span class="bxu-item-block-top">
		<img src="$templateFolder/images/pg-spacer-img.png" class="bxu-spacer"/>
		<span class="bxu-item-block-preview">#preview#</span>
		<span class="bxu-item-load-bar" id="bxu#id#Progress"><span class="bxu-item-load-bar-inner" id="bxu#id#ProgressBar"></span></span>
	</span>
	<span class="bxu-item-block-bottom" onmousedown="BX.eventCancelBubble(event); return true;">
		<span class="bxu-item-block-setting">
			<span class="bxu-item-btn bxu-item-btn-edit" id="#id#Edit" title="$edit"></span>
			<span class="bxu-item-btn bxu-item-btn-turn" id="#id#Turn" title="$turn"></span>
			<span class="bxu-item-btn bxu-item-btn-del" id="#id#Del" title="$del"></span></span>
		<span class="bxu-item-block-desc">#description#</span>
	</span>
</span>
HTML;
$errorThumb = <<<HTML
<span class="bxu-item-block">
	<span class="bxu-item-block-top">
		<img src="$templateFolder/images/pg-spacer-img.png" class="bxu-spacer" />
		<span class="bxu-item-error-cont">
			<span class="bxu-error-icon"></span>
			<span class="bxu-error-text">#error#</span>
		</span>
	</span>
</span>
HTML;

$params = array_merge($arParams["bxu"]->params, array(
	"UPLOADER_ID" => $arParams["UPLOADER_ID"],
	"id" => $arParams["id"],
	"uploadFormData" => "Y",
	"uploadMethod" => "deferred",
	"input" => "bxuUploader".$arParams["id"],
	"dropZone" => "bxuDropzone".$arParams["id"],
	"placeHolder" => "bxuItems".$arParams["id"],
	"errorThumb" => $errorThumb,
	"thumb" => array("className" => "bxu-item"),
	"fields" => array(
		"thumb" => array(
			"template" => $thumb,
			"editorTemplate" => "#description#"
		),
		"description" => array(
			"template" => '<input class="bxu-item-thumb-description-inp" name="description" placeholder="'.GetMessage("MFU_DESCRIPTION").'" value="#description#" type="text" />',
			"editorTemplate" => '<input name="description" placeholder="'.GetMessage("MFU_DESCRIPTION").'" value="#description#" type="text" />',
			"className" => "bx-bxu-thumb-description"
		)
	)));
?>
<div class="pg-main-wrap">
<form id="<?= $arParams["UPLOADER_ID"]?>_form" name="<?= $arParams["UPLOADER_ID"]?>_form" action="<?=  htmlspecialcharsbx($arParams["ACTION_URL"])?>" method="POST" enctype="multipart/form-data" class="bxiu-photo-form">
	<input type="hidden" name="save_upload" id="save_upload" value="Y" />
	<input type="hidden" name="sessid" id="sessid" value="<?= bitrix_sessid()?>" />
	<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
	<input type="hidden" name="photo_resize_size" value="" />
	<input type="hidden" name="photo_public" value="" />
<div class="bxu-thumbnails bxu-thumbnails-start<?=($arParams["USER_SETTINGS"]["template"]=="full" ? "" : " bxu-main-block-reduced-size")?><?
	?><?=($arWatermark["additional"] ? " bxu-thumbnails-settings-are" : "")?>" id="bxuMain<?=$arParams["id"]?>"> <!-- bxu-thumbnails-loading bxu-thumbnails-start-->
	<div class="bxu-top-block"><div class="bxu-top-block-inner">
		<label class="pg-top-bar-text" for="photo_album_id<?=$arParams["UPLOADER_ID"]?>"><?=GetMessage("P_TO_ALBUM")?>:</label>
		<select class="pg-select" name="photo_album_id" id="photo_album_id<?=$arParams["id"]?>" onchange="this.nextSibling.style.display=(this.value=='new'?'':'none');">
			<option value="new" <?=($arParams["SECTION_ID"] == 0 ? "selected" : "")?>> - <?=GetMessage("P_IN_NEW_ALBUM")?> -</option>
		<?if (is_array($arResult["SECTION_LIST"])):?>
			<?foreach ($arResult["SECTION_LIST"] as $key => $val):?>
				<option value="<?=$key?>" <?=($arParams["SECTION_ID"] == $key ? "selected" : "")?>><?=$val?></option>
			<?endforeach;?>
		<?endif;?>
		</select><?
		?><input id="new_album_name<?=$arParams["id"]?>" name="new_album_name" type="text" value="" placeholder="<?=$arParams["NEW_ALBUM_NAME"]?>" <?
			?> class="bxu-top-block-inp"<?if ($arParams["SECTION_ID"] != 0) { ?> style="display: none;" <? } ?>/><?
		?><span class="bxu-loading-block">
			<span class="bxu-loading-block-bar"><span class="bxu-loading-block-bar-inner" id="bxuUploadBar<?=$arParams["id"]?>"></span></span>
			<span class="bxu-loading-block-text"><?=GetMessage("MFU_HAS_BEEN_UPLOADED")?> <span id="bxuUploaded<?=$arParams["id"]?>"></span> <?=GetMessage("MFU_UPLOAD_FROM")?> <span id="bxuForUpload<?=$arParams["id"]?>"></span></span>
			<span class="bxu-loading-block-cancel-btn" id="bxuCancel<?=$arParams["id"]?>"><?=GetMessage("MFU_CANCEL")?></span>
		</span><?
		?><div class="bxu-settings-block">
			<span class="bxu-settings-block-templates">
				<span class="bxu-templates-btn bxu-templates-btn-small<?=($arParams["USER_SETTINGS"]["template"]=="full" ? "" : " bxu-templates-btn-active")?>" id="bxuReduced<?=$arParams["id"]?>" title="<?=GetMessage("MFU_SIMPLIFIED")?>"></span><?
				?><span id="bxuEnlarge<?=$arParams["id"]?>" class="bxu-templates-btn bxu-templates-btn-big<?=($arParams["USER_SETTINGS"]["template"]=="full" ? " bxu-templates-btn-active" : "")?>" title="<?=GetMessage("MFU_NORMAL")?>"></span>
			</span><?
			/*if (!empty($htmlSettings)) { ?><span class="bxu-set-btn" id="bxuSettings<?=$arParams["id"]?>">Settings</span><? }*/
			if ($arParams["UPLOADER_TYPE"] != "form") { ?><span class="bxu-set-btn"><a href="<?= $arParams["MULTIPLE_FORM_URL"]?>"><?= GetMessage("P_SHOW_APPLET")?></a></span><? }
			?>
		</div>
	</div></div>
	<?/*?><?=$htmlSettings?><?*/?>
	<div class="bxu-main-block" id="bxuDropzone<?=$arParams["id"]?>">
		<div class="bxu-start-block">
			<div class="bxu-start-block-spacer-div">
				<img class="bxu-start-block-spacer-img" src="<?=$templateFolder?>/images/pg-start-spacer.png"/>
				<input type="file" id="bxuUploaderStartField<?=$arParams["id"]?>" multiple="multiple" />
			</div>
			<div class="bxu-start-block-cont">
				<img src="<?=$templateFolder?>/images/start-img.png" class="bxu-start-block-img" alt=""/>
				<div class="bxu-start-block-text">
					<?=GetMessage("MFU_UPLOAD1")?>
					<span class="bxu-start-block-description bxu-dnd"><?=GetMessage("MFU_DND")?></span>
				</div>
			</div>
			<div class="bxu-start-block-btn">
				<a class="webform-button webform-button-blue"><?
					?><span class="webform-button-text"><?
						?><?=GetMessage("MFU_UPLOAD")?><?
						?><input type="file" id="bxuUploaderStart<?=$arParams["id"]?>" class="bxu-file-input" multiple="multiple" /><?
					?></span>
				</a>
			</div>
		</div>
		<ul class="bxu-items" id="<?=$params["placeHolder"]?>"></ul>
		<div class="bxu-bottom-block">
			<div class="bxu-bottom-block-shadow-wrap">
				<div class="bxu-bottom-block-shadow"></div>
			</div>
			<div class="bxu-bottom-block-btns">
				<a class="webform-button webform-button-accept" id="bxuStartUploading<?=$arParams["id"]?>">
					<span class="webform-button-text"><?=GetMessage("MFU_UPLOAD")?></span>
				</a>
				<a class="webform-button webform-button-add">
					<span class="webform-button-text"><?=GetMessage("MFU_ADD")?>
						<input type="file" id="bxuUploader<?=$arParams["id"]?>" name="FILE" class="bxu-file-input" multiple="multiple" />
					</span>
				</a>
			</div>
			<div class="bxu-bottom-block-text"><?=GetMessage("MFU_COUNT")?>: <span id="bxuImagesCount<?=$arParams["id"]?>">0</span></div>
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.ready(function(){
		new BX.UploaderTemplateThumbnails(<?=CUtil::PhpToJSObject($params)?>);
	});
</script>
</form>
</div>
<?
}
else
{
/* CONTROLS IN THE TOP OF UPLOADER*/?>
<div class="image-uploader-objects">
<form id="<?= $arParams["UPLOADER_ID"]?>_form" name="<?= $arParams["UPLOADER_ID"]?>_form" action="<?=  htmlspecialcharsbx($arParams["ACTION_URL"])?>" method="POST" enctype="multipart/form-data" class="bxiu-photo-form">
	<input type="hidden" name="save_upload" id="save_upload" value="Y" />
	<input type="hidden" name="sessid" id="sessid" value="<?= bitrix_sessid()?>" />
	<input type="hidden" name="FileCount" value="<?=$arParams["UPLOAD_MAX_FILE"]?>" />
	<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
	<input type="hidden" name="Public" value="<?=($arParams["SHOW_PUBLIC"] == "Y" ? "N" : "Y")?>" />
	<input type="hidden" name="photo_resize_size" value="" />
	<input type="hidden" name="photo_watermark_use" value="" />
	<input type="hidden" name="photo_watermark_type" value="" />
	<input type="hidden" name="photo_watermark_text" value="" />
	<input type="hidden" name="photo_watermark_copyright" value="" />
	<input type="hidden" name="photo_watermark_color" value="" />
	<input type="hidden" name="photo_watermark_size" value="" />
	<input type="hidden" name="photo_watermark_opacity" value="" />
	<input type="hidden" name="photo_watermark_position" value="" />
	<input type="hidden" name="photo_watermark_path" value="" id="<?= $arParams["UPLOADER_ID"]?>_wmark_path"/>
	<input type="hidden" name="photo_public" value="" />

	<div id="bxuMain<?=$arParams["id"]?>" class="bxiu-top-controls<?= ($arWatermark["additional"] ? " bxu-thumbnails-settings-are" : "")?>">
		<div class="bxiu-top-bar">
			<div class="bxiu-album-cont">
				<label for="photo_album_id<?=$arParams["UPLOADER_ID"]?>"><?=GetMessage("P_TO_ALBUM")?>:</label>
				<select id="photo_album_id<?=$arParams["UPLOADER_ID"]?>" name="photo_album_id" onchange="this.nextSibling.style.display=(this.value=='new'?'':'none');">
					<option value="new" <?=(intVal($arParams["SECTION_ID"]) == 0 ? "selected" : "")?>> - <?=GetMessage("P_IN_NEW_ALBUM")?> -</option>
				<?if (is_array($arResult["SECTION_LIST"])):?>
					<?foreach ($arResult["SECTION_LIST"] as $key => $val):?>
						<option value="<?=$key?>" <?=($arParams["SECTION_ID"] == $key ? "selected" : "")?>><?=$val?></option>
					<?endforeach;?>
				<?endif;?>
				</select><?
				?><input id="new_album_name<?=$arParams["UPLOADER_ID"]?>" name="new_album_name" type="text" value="<?= $arParams["NEW_ALBUM_NAME"]?>" <?if ($arParams["SECTION_ID"] != 0) { ?> style="display: none;" <? } ?> />
			</div>
			<? if (!empty($htmlSettings)): /* Additional section link*/?>
			<a class="bxiu-add-set-link" href="javascript:void(0);" id="bxuSettings<?=$arParams["id"]?>"><?= GetMessage("P_ADDITIONAL_SETTINGS")?></a>
			<?endif; /* END Additional section link*/?>
			<a class="bxiu-mode-link" href="<?= $arParams["SIMPLE_FORM_URL"]?>"><?= GetMessage("P_SHOW_FORM")?></a>
		</div><?=$htmlSettings?>
	</div>
</div>
</form>
<?
	if ($arParams["UPLOADER_TYPE"] == "applet") /* Show Image Uploader */
	{
		CImageUploader::ShowScript(array(
			"id" => $arParams["UPLOADER_ID"],
			"layout" => $arParams["APPLET_LAYOUT"] == "simple" ? "OnePane" : "ThreePanes",
			"folderViewMode" => "Thumbnails",
			"uploadViewMode" => "Tiles",
			"height" => $arParams["UPLOADER_HEIGHT"]."px",
			"folderPaneHeight" => round($arParams["UPLOADER_HEIGHT"] / 2),
			"thumbnailJpegQuality" => $arParams["JPEG_QUALITY"],
			"enableCrop" => true,
			"cropRatio" => "0.75",
			"cropMinSize" => "150",
			"fileMask" => "*.jpg;*.jpeg;*.png;*.gif;*.bmp",
			"actionUrl" => $arParams["ACTION_URL"],
			"redirectUrl" => $arParams["REDIRECT_URL"],
			"appendFormName" => $arParams["UPLOADER_ID"]."_form",
			"showAddFileButton" => $arParams["APPLET_LAYOUT"] == "simple",
			"showAddFolderButton" => $arParams["APPLET_LAYOUT"] == "simple",
			"filesPerPackage" => 2, //
			"converters" => $arParams["converters"],
			"maxFileSize" => $arResult["UPLOAD_MAX_FILE_SIZE"],
			"pathToTmp" => $arParams["PATH_TO_TMP"],
			"useWatermark" => true,
			"watermarkConfig" => array(
				"values" => array(
					"use" => $arWatermark["use"],
					"type" => $arWatermark["type"],
					"text" => $arWatermark["text"],
					"color" => $arWatermark["color"],
					"position" => $arWatermark["position"],
					"size" => $arWatermark["size"],
					"opacity" => $arWatermark["opacity"],
					"file" => $arWatermark["file"]
				),

				"rules" => $arParams["WATERMARK_RULES"], // USER | ALL
				"type" => $arParams["WATERMARK_TYPE"], // BOTH | TEXT | PICTURE
				"text" => $arParams["WATERMARK_TEXT"],
				"color" => $arParams["WATERMARK_COLOR"],
				"position" => $arParams["WATERMARK_POSITION"],
				"size" => $arParams["WATERMARK_SIZE"],
				"opacity" => $arParams["WATERMARK_TRANSPARENCY"],
				"file" => $arParams["WATERMARK_FILE_REL"],
				"fileWidth" => $arWatermark["fileWidth"],
				"fileHeight" => $arWatermark["fileHeight"]
			)
		));
	}
	else
	{
		CFlashUploader::ShowScript(array(
			"id" => $arParams["UPLOADER_ID"],
			"height" => $arParams["UPLOADER_HEIGHT"]."px",
			"fileMask" => "[['*.jpg; *.jpeg; *.png; *.gif; *.bmp', '*.jpg;*.jpeg;*.png;*.gif;*.bmp'], ['*.*','*.*']]",
			"actionUrl" => $arParams["ACTION_URL"],
			"redirectUrl" => $arParams["REDIRECT_URL"],
			"appendFormName" => $arParams["UPLOADER_ID"]."_form",
			"converters" => $arParams["converters"],
			"maxFileSize" => $arResult["UPLOAD_MAX_FILE_SIZE"],
			"pathToTmp" => $arParams["PATH_TO_TMP"],
			"thumbnailJpegQuality" => $arParams["JPEG_QUALITY"]
		));
	}
}

if ($arParams["ORIGINAL_SIZE"] || $arResult["UPLOAD_MAX_FILE_SIZE_MB"] && $arParams["ALLOW_UPLOAD_BIG_FILES"] != "Y" || $arParams["MODERATION"] == "Y"):?>
<div class="bxiu-notice <?=($arParams["VIEW_MODE"] == "applet" ? "bxiu-notice-applet" : "bxiu-notice-form")?>">
<? if ($arParams["MODERATION"] == "Y"):?>
	<p><?= GetMessage("P_MODERATION_NITICE");?></p>
<?endif;?>
<? if ($arParams["ORIGINAL_SIZE"]):?>
	<p><?= GetMessage("P_MAX_FILE_DIMENTIONS_NOTICE", Array("#MAX_FILE_DIMENTIONS#" => intVal($arParams["ORIGINAL_SIZE"])));?></p>
<?endif;?>
<? if ($arResult["UPLOAD_MAX_FILE_SIZE_MB"] && $arParams["ALLOW_UPLOAD_BIG_FILES"] != "Y"):?>
	<p><?= GetMessage("P_MAX_FILE_SIZE_NOTICE", Array("#POST_MAX_SIZE_STR#" => $arResult["UPLOAD_MAX_FILE_SIZE_MB"]));?></p>
<?endif;?>
</div>
<?endif;?>