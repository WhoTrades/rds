<?php
class TbHtml
{
    const ICON_COLOR_DEFAULT = '';
    const ICON_COLOR_WHITE = 'glyphicon-white';

    const ICON_ADJUST = 'adjust';
    const ICON_ALIGN_CENTER = 'align-center';
    const ICON_ALIGN_JUSTIFY = 'align-justify';
    const ICON_ALIGN_LEFT = 'align-left';
    const ICON_ALIGN_RIGHT = 'align-right';
    const ICON_ARROW_DOWN = 'arrow-down';
    const ICON_ARROW_LEFT = 'arrow-left';
    const ICON_ARROW_RIGHT = 'arrow-right';
    const ICON_ARROW_UP = 'arrow-up';
    const ICON_ASTERISK = 'asterisk';
    const ICON_BACKWARD = 'backward';
    const ICON_BAN_CIRCLE = 'ban-circle';
    const ICON_BARCODE = 'barcode';
    const ICON_BELL = 'bell';
    const ICON_BOLD = 'bold';
    const ICON_BOOK = 'book';
    const ICON_BOOKMARK = 'bookmark';
    const ICON_BRIEFCASE = 'briefcase';
    const ICON_BULLHORN = 'bullhorn';
    const ICON_CALENDAR = 'calendar';
    const ICON_CAMERA = 'camera';
    const ICON_CERTIFICATE = 'certificate';
    const ICON_CHECK = 'check';
    const ICON_CHEVRON_DOWN = 'chevron-down';
    const ICON_CHEVRON_LEFT = 'chevron-left';
    const ICON_CHEVRON_RIGHT = 'chevron-right';
    const ICON_CHEVRON_UP = 'chevron-up';
    const ICON_CIRCLE_ARROW_DOWN = 'circle-arrow-down';
    const ICON_CIRCLE_ARROW_LEFT = 'circle-arrow-left';
    const ICON_CIRCLE_ARROW_RIGHT = 'circle-arrow-right';
    const ICON_CIRCLE_ARROW_UP = 'circle-arrow-up';
    const ICON_CLOUD = 'cloud';
    const ICON_CLOUD_DOWNLOAD = 'cloud-download';
    const ICON_CLOUD_UPLOAD = 'cloud-upload';
    const ICON_COG = 'cog';
    const ICON_COLLAPSE_DOWN = 'collapse-down';
    const ICON_COLLAPSE_UP = 'collapse-up';
    const ICON_COMMENT = 'comment';
    const ICON_COMPRESSED = 'compressed';
    const ICON_COPYRIGHT_MARK = 'copyright-mark';
    const ICON_CREDIT_CARD = 'credit-card';
    const ICON_CUTLERY = 'cutlery';
    const ICON_DASHBOARD = 'dashboard';
    const ICON_DOWNLOAD = 'download';
    const ICON_DOWNLOAD_ALT = 'download-alt';
    const ICON_EARPHONE = 'earphone';
    const ICON_EDIT = 'edit';
    const ICON_EJECT = 'eject';
    const ICON_ENVELOPE = 'envelope';
    const ICON_EURO = 'euro';
    const ICON_EXCLAMATION_SIGN = 'exclamation-sign';
    const ICON_EXPAND = 'expand';
    const ICON_EXPORT = 'export';
    const ICON_EYE_CLOSE = 'eye-close';
    const ICON_EYE_OPEN = 'eye-open';
    const ICON_FACETIME_VIDEO = 'facetime-video';
    const ICON_FAST_BACKWARD = 'fast-backward';
    const ICON_FAST_FORWARD = 'fast-forward';
    const ICON_FILE = 'file';
    const ICON_FILM = 'film';
    const ICON_FILTER = 'filter';
    const ICON_FIRE = 'fire';
    const ICON_FLAG = 'flag';
    const ICON_FLASH = 'flash';
    const ICON_FLOPPY_DISK = 'floppy-disk';
    const ICON_FLOPPY_OPEN = 'floppy-open';
    const ICON_FLOPPY_REMOVE = 'floppy-remove';
    const ICON_FLOPPY_SAVE = 'floppy-save';
    const ICON_FLOPPY_SAVED = 'floppy-saved';
    const ICON_FOLDER_CLOSE = 'folder-close';
    const ICON_FOLDER_OPEN = 'folder-open';
    const ICON_FONT = 'font';
    const ICON_FORWARD = 'forward';
    const ICON_FULLSCREEN = 'fullscreen';
    const ICON_GBP = 'gbp';
    const ICON_GIFT = 'gift';
    const ICON_GLASS = 'glass';
    const ICON_GLOBE = 'globe';
    const ICON_HAND_DOWN = 'hand-down';
    const ICON_HAND_LEFT = 'hand-left';
    const ICON_HAND_RIGHT = 'hand-right';
    const ICON_HAND_UP = 'hand-up';
    const ICON_HD_VIDEO = 'hd-video';
    const ICON_HDD = 'hdd';
    const ICON_HEADER = 'header';
    const ICON_HEADPHONES = 'headphones';
    const ICON_HEART = 'heart';
    const ICON_HEART_EMPTY = 'heart-empty';
    const ICON_HOME = 'home';
    const ICON_IMPORT = 'import';
    const ICON_INBOX = 'inbox';
    const ICON_INDENT_LEFT = 'indent-left';
    const ICON_INDENT_RIGHT = 'indent-right';
    const ICON_INFO_SIGN = 'info-sign';
    const ICON_ITALIC = 'italic';
    const ICON_LEAF = 'leaf';
    const ICON_LINK = 'link';
    const ICON_LIST = 'list';
    const ICON_LIST_ALT = 'list-alt';
    const ICON_LOCK = 'lock';
    const ICON_LOG_IN = 'log-in';
    const ICON_LOG_OUT = 'log-out';
    const ICON_MAGNET = 'magnet';
    const ICON_MAP_MARKER = 'map-marker';
    const ICON_MINUS = 'minus';
    const ICON_MINUS_SIGN = 'minus-sign';
    const ICON_MOVE = 'move';
    const ICON_MUSIC = 'music';
    const ICON_NEW_WINDOW = 'new-window';
    const ICON_OFF = 'off';
    const ICON_OK = 'ok';
    const ICON_OK_CIRCLE = 'ok-circle';
    const ICON_OK_SIGN = 'ok-sign';
    const ICON_OPEN = 'open';
    const ICON_PAPERCLIP = 'paperclip';
    const ICON_PAUSE = 'pause';
    const ICON_PENCIL = 'pencil';
    const ICON_PHONE = 'phone';
    const ICON_PHONE_ALT = 'phone-alt';
    const ICON_PICTURE = 'picture';
    const ICON_PLANE = 'plane';
    const ICON_PLAY = 'play';
    const ICON_PLAY_CIRCLE = 'play-circle';
    const ICON_PLUS = 'plus';
    const ICON_PLUS_SIGN = 'plus-sign';
    const ICON_PRINT = 'print';
    const ICON_PUSHPIN = 'pushpin';
    const ICON_QRCODE = 'qrcode';
    const ICON_QUESTION_SIGN = 'question-sign';
    const ICON_RANDOM = 'random';
    const ICON_RECORD = 'record';
    const ICON_REFRESH = 'refresh';
    const ICON_REGISTRATION_MARK = 'registration-mark';
    const ICON_REMOVE = 'remove';
    const ICON_REMOVE_CIRCLE = 'remove-circle';
    const ICON_REMOVE_SIGN = 'remove-sign';
    const ICON_REPEAT = 'repeat';
    const ICON_RESIZE_FULL = 'resize-full';
    const ICON_RESIZE_HORIZONTAL = 'resize-horizontal';
    const ICON_RESIZE_SMALL = 'resize-small';
    const ICON_RESIZE_VERTICAL = 'resize-vertical';
    const ICON_RETWEET = 'retweet';
    const ICON_ROAD = 'road';
    const ICON_SAVE = 'save';
    const ICON_SAVED = 'saved';
    const ICON_SCREENSHOT = 'screenshot';
    const ICON_SD_VIDEO = 'sd-video';
    const ICON_SEARCH = 'search';
    const ICON_SEND = 'send';
    const ICON_SHARE = 'share';
    const ICON_SHARE_ALT = 'share-alt';
    const ICON_SHOPPING_CART = 'shopping-cart';
    const ICON_SIGNAL = 'signal';
    const ICON_SORT = 'sort';
    const ICON_SORT_BY_ALPHABET = 'sort-by-alphabet';
    const ICON_SORT_BY_ALPHABET_ALT = 'sort-by-alphabet-alt';
    const ICON_SORT_BY_ATTRIBUTES = 'sort-by-attributes';
    const ICON_SORT_BY_ATTRIBUTES_ALT = 'sort-by-attributes-alt';
    const ICON_SORT_BY_ORDER = 'sort-by-order';
    const ICON_SORT_BY_ORDER_ALT = 'sort-by-order-alt';
    const ICON_SOUND_5_1 = 'sound-5-1';
    const ICON_SOUND_6_1 = 'sound-6-1';
    const ICON_SOUND_7_1 = 'sound-7-1';
    const ICON_SOUND_DOLBY = 'sound-dolby';
    const ICON_SOUND_STEREO = 'sound-stereo';
    const ICON_STAR = 'star';
    const ICON_STAR_EMPTY = 'star-empty';
    const ICON_STATS = 'stats';
    const ICON_STEP_BACKWARD = 'step-backward';
    const ICON_STEP_FORWARD = 'step-forward';
    const ICON_STOP = 'stop';
    const ICON_SUBTITLES = 'subtitles';
    const ICON_TAG = 'tag';
    const ICON_TAGS = 'tags';
    const ICON_TASKS = 'tasks';
    const ICON_TEXT_HEIGHT = 'text-height';
    const ICON_TEXT_WIDTH = 'text-width';
    const ICON_TH = 'th';
    const ICON_TH_LARGE = 'th-large';
    const ICON_TH_LIST = 'th-list';
    const ICON_THUMBS_DOWN = 'thumbs-down';
    const ICON_THUMBS_UP = 'thumbs-up';
    const ICON_TIME = 'time';
    const ICON_TINT = 'tint';
    const ICON_TOWER = 'tower';
    const ICON_TRANSFER = 'transfer';
    const ICON_TRASH = 'trash';
    const ICON_TREE_CONIFER = 'tree-conifer';
    const ICON_TREE_DECIDUOUS = 'tree-deciduous';
    const ICON_UNCHECKED = 'unchecked';
    const ICON_UPLOAD = 'upload';
    const ICON_USD = 'usd';
    const ICON_USER = 'user';
    const ICON_VOLUME_DOWN = 'volume-down';
    const ICON_VOLUME_OFF = 'volume-off';
    const ICON_VOLUME_UP = 'volume-up';
    const ICON_WARNING_SIGN = 'warning-sign';
    const ICON_WRENCH = 'wrench';
    const ICON_ZOOM_IN = 'zoom-in';
    const ICON_ZOOM_OUT = 'zoom-out';


    const ALERT_COLOR_DEFAULT = '';
    const ALERT_COLOR_INFO = 'info';
    const ALERT_COLOR_SUCCESS = 'success';
    const ALERT_COLOR_WARNING = 'warning';
    const ALERT_COLOR_DANGER = 'danger';

    /**
     * Generates an alert.
     * @param string $color the color of the alert.
     * @param string $message the message to display.
     * @param array $htmlOptions additional HTML options.
     * @return string the generated alert.
     */
    public static function alert($color, $message, $htmlOptions = array())
    {
        $htmlOptions = array_merge(['class' => '', 'style' => ''], $htmlOptions);
        $htmlOptions['class'] .= ' alert';
        if (!empty($color)) {
            $htmlOptions['class'] .= ' alert-' . $color;
        }
        $closeText = isset($htmlOptions['closeText']) ? $htmlOptions['closeText'] : '&times;';
        $output = "<div style='{$htmlOptions['style']}' class='{$htmlOptions['class']}'>";
        $output .= $closeText !== false ? "<a href='#' class='close'>$closeText</a>" : "";
        $output .= $message;
        $output .= '</div>';

        return $output;
    }
}
