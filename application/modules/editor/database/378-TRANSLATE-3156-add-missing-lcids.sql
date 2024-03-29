-- update lcids in table LEK_langugaes, based on informations found at https://www.ryadel.com/en/microsoft-windows-lcid-list-decimal-and-hex-all-locale-codes-ids/

-- first we have to correct some wrong lcids
-- lcid 4096 = Ox1000 is no allowed lcid, see: https://learn.microsoft.com/en-us/openspecs/windows_protocols/ms-lcid/926e694f-1797-4418-a922-343d1c5e91a6
-- therefore we have to remove that lcid
UPDATE `LEK_languages` SET `lcid`=NULL WHERE `lcid`="4096";

-- there is no lcid for language "sr-RS", there is only one for "sr-Latn-RS"
UPDATE `LEK_languages` SET `lcid`=NULL WHERE `rfc5646`="sr-RS";

-- now update languages compared by the field rfc5646
-- @TODO after this, still some entries have no lcids. But at least now over 100 do have one which didnt have one before
UPDATE `LEK_languages` SET `lcid`="7" WHERE `rfc5646`="de";
UPDATE `LEK_languages` SET `lcid`="9" WHERE `rfc5646`="en";
UPDATE `LEK_languages` SET `lcid`="10" WHERE `rfc5646`="es";
UPDATE `LEK_languages` SET `lcid`="12" WHERE `rfc5646`="fr";
UPDATE `LEK_languages` SET `lcid`="16" WHERE `rfc5646`="it";
UPDATE `LEK_languages` SET `lcid`="2" WHERE `rfc5646`="bg";
UPDATE `LEK_languages` SET `lcid`="6" WHERE `rfc5646`="da";
UPDATE `LEK_languages` SET `lcid`="37" WHERE `rfc5646`="et";
UPDATE `LEK_languages` SET `lcid`="11" WHERE `rfc5646`="fi";
UPDATE `LEK_languages` SET `lcid`="8" WHERE `rfc5646`="el";
UPDATE `LEK_languages` SET `lcid`="26" WHERE `rfc5646`="hr";
UPDATE `LEK_languages` SET `lcid`="19" WHERE `rfc5646`="nl";
UPDATE `LEK_languages` SET `lcid`="20" WHERE `rfc5646`="no";
UPDATE `LEK_languages` SET `lcid`="21" WHERE `rfc5646`="pl";
UPDATE `LEK_languages` SET `lcid`="22" WHERE `rfc5646`="pt";
UPDATE `LEK_languages` SET `lcid`="24" WHERE `rfc5646`="ro";
UPDATE `LEK_languages` SET `lcid`="25" WHERE `rfc5646`="ru";
UPDATE `LEK_languages` SET `lcid`="29" WHERE `rfc5646`="sv";
UPDATE `LEK_languages` SET `lcid`="27" WHERE `rfc5646`="sk";
UPDATE `LEK_languages` SET `lcid`="36" WHERE `rfc5646`="sl";
UPDATE `LEK_languages` SET `lcid`="54" WHERE `rfc5646`="af";
UPDATE `LEK_languages` SET `lcid`="28" WHERE `rfc5646`="sq";
UPDATE `LEK_languages` SET `lcid`="43" WHERE `rfc5646`="hy";
UPDATE `LEK_languages` SET `lcid`="44" WHERE `rfc5646`="az";
UPDATE `LEK_languages` SET `lcid`="69" WHERE `rfc5646`="bn";
UPDATE `LEK_languages` SET `lcid`="30746" WHERE `rfc5646`="bs";
UPDATE `LEK_languages` SET `lcid`="55" WHERE `rfc5646`="ka";
UPDATE `LEK_languages` SET `lcid`="71" WHERE `rfc5646`="gu";
UPDATE `LEK_languages` SET `lcid`="57" WHERE `rfc5646`="hi";
UPDATE `LEK_languages` SET `lcid`="112" WHERE `rfc5646`="ig";
UPDATE `LEK_languages` SET `lcid`="33" WHERE `rfc5646`="id";
UPDATE `LEK_languages` SET `lcid`="15" WHERE `rfc5646`="is";
UPDATE `LEK_languages` SET `lcid`="17" WHERE `rfc5646`="ja";
UPDATE `LEK_languages` SET `lcid`="83" WHERE `rfc5646`="km";
UPDATE `LEK_languages` SET `lcid`="75" WHERE `rfc5646`="kn";
UPDATE `LEK_languages` SET `lcid`="63" WHERE `rfc5646`="kk";
UPDATE `LEK_languages` SET `lcid`="3" WHERE `rfc5646`="ca";
UPDATE `LEK_languages` SET `lcid`="64" WHERE `rfc5646`="ky";
UPDATE `LEK_languages` SET `lcid`="18" WHERE `rfc5646`="ko";
UPDATE `LEK_languages` SET `lcid`="38" WHERE `rfc5646`="lv";
UPDATE `LEK_languages` SET `lcid`="39" WHERE `rfc5646`="lt";
UPDATE `LEK_languages` SET `lcid`="76" WHERE `rfc5646`="ml";
UPDATE `LEK_languages` SET `lcid`="62" WHERE `rfc5646`="ms";
UPDATE `LEK_languages` SET `lcid`="58" WHERE `rfc5646`="mt";
UPDATE `LEK_languages` SET `lcid`="78" WHERE `rfc5646`="mr";
UPDATE `LEK_languages` SET `lcid`="47" WHERE `rfc5646`="mk";
UPDATE `LEK_languages` SET `lcid`="70" WHERE `rfc5646`="pa";
UPDATE `LEK_languages` SET `lcid`="99" WHERE `rfc5646`="ps";
UPDATE `LEK_languages` SET `lcid`="41" WHERE `rfc5646`="fa";
UPDATE `LEK_languages` SET `lcid`="31770" WHERE `rfc5646`="sr";
UPDATE `LEK_languages` SET `lcid`="48" WHERE `rfc5646`="st";
UPDATE `LEK_languages` SET `lcid`="119" WHERE `rfc5646`="so";
UPDATE `LEK_languages` SET `lcid`="65" WHERE `rfc5646`="sw";
UPDATE `LEK_languages` SET `lcid`="40" WHERE `rfc5646`="tg";
UPDATE `LEK_languages` SET `lcid`="73" WHERE `rfc5646`="ta";
UPDATE `LEK_languages` SET `lcid`="74" WHERE `rfc5646`="te";
UPDATE `LEK_languages` SET `lcid`="30" WHERE `rfc5646`="th";
UPDATE `LEK_languages` SET `lcid`="81" WHERE `rfc5646`="bo";
UPDATE `LEK_languages` SET `lcid`="5" WHERE `rfc5646`="cs";
UPDATE `LEK_languages` SET `lcid`="50" WHERE `rfc5646`="tn";
UPDATE `LEK_languages` SET `lcid`="31" WHERE `rfc5646`="tr";
UPDATE `LEK_languages` SET `lcid`="66" WHERE `rfc5646`="tk";
UPDATE `LEK_languages` SET `lcid`="34" WHERE `rfc5646`="uk";
UPDATE `LEK_languages` SET `lcid`="14" WHERE `rfc5646`="hu";
UPDATE `LEK_languages` SET `lcid`="67" WHERE `rfc5646`="uz";
UPDATE `LEK_languages` SET `lcid`="42" WHERE `rfc5646`="vi";
UPDATE `LEK_languages` SET `lcid`="35" WHERE `rfc5646`="be";
UPDATE `LEK_languages` SET `lcid`="52" WHERE `rfc5646`="xh";
UPDATE `LEK_languages` SET `lcid`="106" WHERE `rfc5646`="yo";
UPDATE `LEK_languages` SET `lcid`="53" WHERE `rfc5646`="zu";
UPDATE `LEK_languages` SET `lcid`="1" WHERE `rfc5646`="ar";
UPDATE `LEK_languages` SET `lcid`="29740" WHERE `rfc5646`="az-Cyrl";
UPDATE `LEK_languages` SET `lcid`="82" WHERE `rfc5646`="cy";
UPDATE `LEK_languages` SET `lcid`="101" WHERE `rfc5646`="dv";
UPDATE `LEK_languages` SET `lcid`="4096" WHERE `rfc5646`="eo";
UPDATE `LEK_languages` SET `lcid`="45" WHERE `rfc5646`="eu";
UPDATE `LEK_languages` SET `lcid`="56" WHERE `rfc5646`="fo";
UPDATE `LEK_languages` SET `lcid`="86" WHERE `rfc5646`="gl";
UPDATE `LEK_languages` SET `lcid`="146" WHERE `rfc5646`="ku";
UPDATE `LEK_languages` SET `lcid`="13" WHERE `rfc5646`="he";
UPDATE `LEK_languages` SET `lcid`="87" WHERE `rfc5646`="kok";
UPDATE `LEK_languages` SET `lcid`="129" WHERE `rfc5646`="mi";
UPDATE `LEK_languages` SET `lcid`="80" WHERE `rfc5646`="mn";
UPDATE `LEK_languages` SET `lcid`="31764" WHERE `rfc5646`="nb";
UPDATE `LEK_languages` SET `lcid`="30740" WHERE `rfc5646`="nn";
UPDATE `LEK_languages` SET `lcid`="79" WHERE `rfc5646`="sa";
UPDATE `LEK_languages` SET `lcid`="59" WHERE `rfc5646`="se";
UPDATE `LEK_languages` SET `lcid`="3131" WHERE `rfc5646`="se-FI";
UPDATE `LEK_languages` SET `lcid`="2107" WHERE `rfc5646`="se-SE";
UPDATE `LEK_languages` SET `lcid`="27674" WHERE `rfc5646`="sr-Cyrl";
UPDATE `LEK_languages` SET `lcid`="7194" WHERE `rfc5646`="sr-Cyrl-BA";
UPDATE `LEK_languages` SET `lcid`="90" WHERE `rfc5646`="syr";
UPDATE `LEK_languages` SET `lcid`="68" WHERE `rfc5646`="tt";
UPDATE `LEK_languages` SET `lcid`="49" WHERE `rfc5646`="ts";
UPDATE `LEK_languages` SET `lcid`="32" WHERE `rfc5646`="ur";
UPDATE `LEK_languages` SET `lcid`="30724" WHERE `rfc5646`="zh";
UPDATE `LEK_languages` SET `lcid`="94" WHERE `rfc5646`="am";
UPDATE `LEK_languages` SET `lcid`="131" WHERE `rfc5646`="co";
UPDATE `LEK_languages` SET `lcid`="98" WHERE `rfc5646`="fy";
UPDATE `LEK_languages` SET `lcid`="60" WHERE `rfc5646`="ga";
UPDATE `LEK_languages` SET `lcid`="145" WHERE `rfc5646`="gd";
UPDATE `LEK_languages` SET `lcid`="104" WHERE `rfc5646`="ha";
UPDATE `LEK_languages` SET `lcid`="117" WHERE `rfc5646`="haw";
UPDATE `LEK_languages` SET `lcid`="118" WHERE `rfc5646`="la";
UPDATE `LEK_languages` SET `lcid`="110" WHERE `rfc5646`="lb";
UPDATE `LEK_languages` SET `lcid`="84" WHERE `rfc5646`="lo";
UPDATE `LEK_languages` SET `lcid`="85" WHERE `rfc5646`="my";
UPDATE `LEK_languages` SET `lcid`="97" WHERE `rfc5646`="ne";
UPDATE `LEK_languages` SET `lcid`="72" WHERE `rfc5646`="or";
UPDATE `LEK_languages` SET `lcid`="135" WHERE `rfc5646`="rw";
UPDATE `LEK_languages` SET `lcid`="89" WHERE `rfc5646`="sd";
UPDATE `LEK_languages` SET `lcid`="91" WHERE `rfc5646`="si";
UPDATE `LEK_languages` SET `lcid`="128" WHERE `rfc5646`="ug";
UPDATE `LEK_languages` SET `lcid`="61" WHERE `rfc5646`="yi";
UPDATE `LEK_languages` SET `lcid`="100" WHERE `rfc5646`="fil";
UPDATE `LEK_languages` SET `lcid`="140" WHERE `rfc5646`="prs";
UPDATE `LEK_languages` SET `lcid`="9242" WHERE `rfc5646`="sr-Latn-RS";