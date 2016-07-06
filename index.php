<?php

if (array_key_exists('source', $_POST))
	{
	$source = $_POST['source'];
	$h_offset = $_POST['h_offset'];
	$v_offset = $_POST['v_offset'];
	$changed = preg_replace_callback('/#([0-9a-fA-F]*)(\W)/', Change_Color, $source);
	}
else
	{
	$source = "h1 {\n    color: #a00;\n}\np {\n    background-color: #ffcc99;\n    color: #004;\n}\n";
	$h_offset = 0;
	$v_offset = 0;
	}

function Change_Color($matches)
	{
	global $h_offset;
	global $v_offset;
	$color = $matches[1];
	if (strlen($color) == 6)
		{
		$rgb = array(
			hexdec(substr($color, 0, 2)),
			hexdec(substr($color, 2, 2)),
			hexdec(substr($color, 4, 2)),
			);
		}
	elseif (strlen($color) == 3)
		{
		$rgb = array(
			hexdec(substr($color, 0, 1).substr($color, 0, 1)),
			hexdec(substr($color, 1, 1).substr($color, 1, 1)),
			hexdec(substr($color, 2, 1).substr($color, 2, 1)),
			);
		}
	else
		{
		return $matches[0];
		}
	$hsv = RGBtoHSV($rgb);
	$hsv[0] = $hsv[0] + $h_offset;
	$hsv[2] = $hsv[2] + $v_offset;
	if ($hsv[2] >= 256)
		{
		$hsv[2] = 255;
		}
	$rgb = HSVtoRGB($hsv);
	$color = '#'.sprintf('%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
	return
		$color.
		' /* <span style="background-color: '.
		$color.
		'; display: inline-block; width: 5em;">&nbsp;</span> */'.
		$matches[2];
	}

/**
 * RGB配列 を HSV配列 へ変換します
 *
 * @param   array   $arr            array(r, g, b) ※ r/g/b は 0～255 の数値
 * @param   bool    $coneModel      円錐モデルにするか
 * @return  array   array(h, s, v) ※h は 0～360の数値、s/v は 0～255 の数値
 */
function RGBtoHSV($arr, $coneModel = false)
{
	$h = 0; // 0..360
	$s = 0; // 0..255
	$v = 0; // 0..255

	$max = max($arr);
	$min = min($arr);

	// hue の計算
	if ($max == $min) {
		$h = 0; // 本来は定義されないが、仮に0を代入
	} else if ($max == $arr[0]) {
		// MAX = R
		// 60 * (G-B)/(MAX-MIN) + 0
		$h = 60 * ($arr[1] - $arr[2]) / ($max - $min) + 0;
	} else if ($max == $arr[1]) {
		// MAX = G
		// 60 * (B-R)/(MAX-MIN) + 120
		$h = (60 * ($arr[2] - $arr[0]) / ($max - $min)) + 120;
	} else {
		// MAX = B
		// 60 * (R-G)/(MAX-MIN) + 240
		$h = (60 * ($arr[0] - $arr[1]) / ($max - $min)) + 240;
	}

	while ($h < 0) {
		$h += 360;
	}

	// saturation の計算
	if ($coneModel) {
		// 円錐モデルの場合
		$s = $max - $min;
	} else {
		if ($max == 0) {
			// 本来は定義されないが、仮に0を代入
			$s = 0;
		} else {
			$s = ($max - $min) / $max * 255;
		}
	}

	// value の計算
	$v = $max;

	return array(
		$h, // H
		$s, // S
		$v, // V
	);
}

/**
 * HSV配列 を RGB配列 へ変換します
 *
 * @param   array   $arr            array(h, s, v) ※h は 0～360の数値、s/v は 0～255 の数値
 * @return  array   array(r, g, b) ※ r/g/b は 0～255 の数値
 */
function HSVtoRGB($arr)
{
	$r = 0; // 0..255
	$g = 0; // 0..255
	$b = 0; // 0..255

	while ($arr[0] < 0) {
	  $arr[0] += 360;
	}

	$arr[0] = $arr[0] % 360;

	// 特別な場合
	if ($arr[1] == 0) {
		// S = 0.0
		// → RGB は V に等しい
		return array(
			round($arr[2]),
			round($arr[2]),
			round($arr[2]),
		);
	}

	$arr[1] = $arr[1] / 255;


	// Hi = floor(H/60) mod 6
	$i = floor($arr[0] / 60) % 6;
	// f = H/60 - Hi
	$f = ($arr[0] / 60) - $i;

	// p = V (1 - S)
	$p = $arr[2] * (1 - $arr[1]);
	// q = V (1 - fS)
	$q = $arr[2] * (1 - $f * $arr[1]);
	// t = V (1 - (1 - f) S)
	$t = $arr[2] * (1 - (1 - $f) * $arr[1]);

	switch ($i) {
		case 0 :
			// R = V, G = t, B = p
			$r = $arr[2];
			$g = $t;
			$b = $p;
			break;
		case 1 :
			// R = q, G = V, B = p
			$r = $q;
			$g = $arr[2];
			$b = $p;
			break;
		case 2 :
			// R = p, G = V, B = t
			$r = $p;
			$g = $arr[2];
			$b = $t;
			break;
		case 3 :
			// R = p, G = q, B = V
			$r = $p;
			$g = $q;
			$b = $arr[2];
			break;
		case 4 :
			// R = t, G = p, B = V
			$r = $t;
			$g = $p;
			$b = $arr[2];
			break;
		case 5 :
			// R = V, G = p, B = q
			$r = $arr[2];
			$g = $p;
			$b = $q;
			break;
	}

	return array(
		round($r), // r
		round($g), // g
		round($b), // b
	);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>CSS color changer</title>
<style>
h1 {
	margin: 15px;
	font-family: sans-serif;
}
p, label, textarea, input, div {
	display: block;
	margin: 15px;
	margin-top: 0;
	font-size: 14px;
	font-family: sans-serif;
}
label {
	margin-bottom: 0;
}
textarea {
	font-family: monospace;
	font-size: 12px;
	width: 930px;
	height: 20em;
}
input[type="text"] {
	width: 10em;
}
input[type="submit"] {
	padding: 5px 20px;
	background-color: #fff;
	border-color: #000;
	border: 1px solid;
}
div {
	font-family: monospace;
	font-size: 12px;
	margin-top: 20px;
	padding: 5px;
	width: 930px;
	border-color: #000;
	border: 1px solid;
	white-space: pre;
}
</style>
</head>
<body>
<h1>CSS color changer</h1>
<p>
CSSの中のカラーコードの色相と明度をまとめて変換します。<br>
サイトのカラーバリエーションを作るときの大まかな変換のために作りました。<br>
色相だけを変えようと思ったのですが、青系統に変えると見た目が暗い感じになるので明度も変えられるようにしました。<br>
対応するカラーコードは#xxxxxxと#xxxの形式です。<br>
変換はサーバー側で行いますが変換前後のCSSはサーバーには保存しません。<br>
<br>
RGBとHSVの相互変換は次のページのロジックを使わせていただきました。<br>
<a href="http://d.hatena.ne.jp/ja9/20100831/1283181870" target="_blank">
RGB色空間とHSV色空間の相互変換 PHP版 - 今日も適当ダイアリー http://d.hatena.ne.jp/ja9/20100831/1283181870<br>
</a>
</p>
<form method="POST">
<label>CSS</label>
<textarea name="source"><?php echo $source ?></textarea>
<label>色相オフセット (0〜359)</label>
<input type="text" name="h_offset" value="<?php echo $h_offset ?>">
<label>明度オフセット (0〜255)</label>
<input type="text" name="v_offset" value="<?php echo $v_offset ?>">
<input type="submit" value="変換">
<div><?php echo $changed ?></div>
</form>
</body>
</html>
