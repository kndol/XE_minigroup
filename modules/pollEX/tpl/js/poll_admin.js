/**
 * @file   modules/pollex/js/poll_admin.js
 * @author KnDol (kndol@kndol.net)
 * @brief  pollex 모듈의 관리자용 javascript
 **/

/* 위젯 코드 생성시 스킨을 고르면 컬러셋의 정보를 표시 */
function doDisplaySkinColorset(a, b) {
    var c = a.options[a.selectedIndex].value,
        d = new Array;
    d.skin = c, d.colorset = b;
    var e = new Array("error", "message", "colorset_list");
    exec_xml("pollex", "getPollGetColorsetList", d, completeGetSkinColorset, e, d)
}

/* 서버에서 받아온 컬러셋을 표시 */
function completeGetSkinColorset(a, b, c, d) {
    for (var e = get_by_id("fo_poll").poll_colorset, f = e.options.length, g = c.colorset, h = 0; f > h; h++) e.remove(0);
    for (var i = a.colorset_list.split("\n"), j = 0, h = 0; h < i.length; h++) {
        var k = i[h].split("|@|");
        g && g == k[0] && (j = h);
        var l = new Option(k[1], k[0], !1, !1);
        e.options.add(l)
    }
    e.selectedIndex = j
}

/* 관리자 페이지에서 선택된 설문조사 원본글로 이동하는 함수 */
function doMovePoll(a, b) {
    var c = new Array;
    c.poll_srl = a, c.upload_target_srl = b;
    var d = new Array("error", "message", "document_srl", "comment_srl");
    exec_xml("pollex", "getPollAdminTarget", c, completeMovePoll, d)
}

function completeMovePoll(a, b) {
    var c = a.document_srl,
        d = a.comment_srl,
        e = request_uri.setQuery("document_srl", c);
    d && (e = e + "#comment_" + d), winopen(e, "pollTarget")
}

function checkSearch(a) {
    return "" == a.search_target.value ? (alert(xe.lang.msg_empty_search_target), !1) : "" == a.search_keyword.value ? (alert(xe.lang.msg_empty_search_keyword), !1) : void 0
}
jQuery(function(a) {
    a("#pollList").submit(function(b) {
        var c = a("#pollList tbody :checked").length;
        if (0 == c) return b.stopPropagation(), alert(xe.lang.msg_select_poll), !1;
        var d = xe.lang.confirm_poll_delete.replace("%s", c);
        return confirm(d) ? void 0 : (b.stopPropagation(), !1)
    })
});
