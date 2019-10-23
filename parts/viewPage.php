
<script>
var datas = {
	views: {
		<?php
		$views = [];
		$segments = [];
		foreach(glob('imgs/*') as $viewPath) {
			$view = basename($viewPath);
			$views[] = $view;
			?>
			<?=$view?>: {
				hector: '<?=$viewPath?>/hector.png',
				segments: [
			<?php
			foreach(glob("$viewPath/segment_*.png") as $img) {
				preg_match('/\/segment_([0-9]+).png/', $img, $output_array);
				$nb = $output_array[1];
				$segments[$nb] = $img;
				?>
					<?=$nb?>,
				<?php
			}
			?>
				]
			},
			<?php
		}
		ksort($segments);
		?>
	},
	segments: {
		<?php foreach($segments as $k=>$v) { ?>
			<?=$k?>: '<?=$v?>',
		<?php } ?>
		
	},
	callstack: 0,
	view: '',
	size: {
		width: 550,
		height: 290
	},
	segment: null,
	blink: 0,
};
</script>


<table class="box">
	
	<tr>
		<td class="hector"><canvas id="canvas" width="1080" height="720"></canvas></td>
        <td class="bouton" style="text-align: center;">
            <?php
            foreach($views as $view) {
                ?>
                <button class="buttonView" id="btn_<?=$view?>" onclick="select_view('<?=$view?>');"><?=$language['views'][$view]?></button>
                <?php
            }
            ?>
        </td>

        <!-- Paramétrage -->
		<td class="param" valign="top" style="padding-left: 20%;">
			<h1 style="font-size: 3em;"><?=$language['hectorTitle']?></h1>
			<div><?=$language['commandHector']?></div>
			<br>
			<select id="selectSegment" onchange="changeSegment()">
				<option value="-1">---</option>
				<?php foreach($segments as $k=>$v) { ?>
				<option value="<?=$k?>">Segment <?=$k?></option>
				<?php } ?>
			</select>
			
			<input type="color" onchange="changeColorSegment();" id="colorSegment">
			
			<form action="post.php" method="POST">
				<input type="hidden" id="datas" name="datas"/><br>
				<input type="hidden" name="language" value="<?=$languageid?>"/><br>
				<input type="submit" value="<?=$language['save']?>"/>
			</form>
		</td>
	</tr>
</table>



<style>
img {
	/* display: none; */
	border: 1px solid black;
}

canvas {
	border: 1px solid black;
}
</style>

<script>

window.onload = function() {
	console.log('Chargement des images');
	loadImages(function() {
		select_view('<?=$views[0]?>');
		changeSegment();
		exportDatas();
		
		setInterval(function() {
			datas.blink = (datas.blink+1)%3;
			actScreen();
		}, 200);
	});
};

function exportDatas() {
	var idatas = {};
	for(var nb in datas.segments) {
		var data = {};
		for(var k in datas.segments[nb]) {
			if(k != 'image')
				data[k] = datas.segments[nb][k];
		}
		idatas[nb] = data;
	}
	document.getElementById('datas').value = JSON.stringify(idatas);
}

function rgbToHex(r, g, b) {
	return '#' + intToHex(r) + intToHex(g) + intToHex(b);
}

function intToHex(v) { 
	var hex = Number(v).toString(16);
	if (hex.length < 2) {
		hex = "0" + hex;
	}
	return hex;
}

function changeColorSegment() {
	var hex = document.getElementById('colorSegment').value;
	hex = hex.replace('#','');
    datas.segments[datas.segment].color.r = parseInt(hex.substring(0,2), 16);
    datas.segments[datas.segment].color.g = parseInt(hex.substring(2,4), 16);
    datas.segments[datas.segment].color.b = parseInt(hex.substring(4,6), 16);
	
	exportDatas();
	actScreen();
}

function changeSegment() {
	datas.segment = document.getElementById('selectSegment').value;
	if(datas.segment == -1) {
		document.getElementById('colorSegment').style.display = 'none';
		return;
	}
	document.getElementById('colorSegment').style.display = 'inline';
	document.getElementById('colorSegment').value = rgbToHex(
		datas.segments[datas.segment].color.r,
		datas.segments[datas.segment].color.g,
		datas.segments[datas.segment].color.b
	);
}

function loadImage(url, callback) {
	var newImg = new Image;
	newImg.onload = callback
	newImg.src = url;
	return newImg;
}

function loadImages(callback) {
	
	var func = function() {
		datas.callstack--;
		
		if(datas.callstack == 0) {
			callback();
		}
	};
	
	datas.callstack++;
	
	for(var viewName in datas.views) {
		var view = datas.views[viewName];
		
		if((typeof view.hector) == 'string') {
			datas.callstack++;
			view.hector = loadImage(view.hector, func);
		}
		
	}
	
	for(var segmentId in datas.segments) {
		if((typeof datas.segments[segmentId]) == 'string') {
			datas.callstack++;
			datas.segments[segmentId] = {
				image: loadImage(datas.segments[segmentId], func),
				color: {r: 255, g: 255, b: 255}
			};
		}
	}
	
	datas.callstack--;
	func();
	
	
}

function select_view(view) {
	if(datas.view != '') document.getElementById('btn_' + datas.view).classList.remove("btnViewActive");
	datas.view = view;
	document.getElementById('btn_' + view).classList.add("btnViewActive");
	const options = document.getElementById('selectSegment').options;

    while (options.length) {
        options.remove(0);
    }

    for(var k in datas.views[view].segments) {
        const nb = (datas.views[view].segments[k]);
        var car = new Option("Segment " + nb, nb);
        options.add(car);
    }

	actScreen();
}

function actScreen() {
	var canvas = document.getElementById('canvas');
	var ctx = canvas.getContext("2d");
	
	canvas.width = datas.size.width;
	canvas.height = datas.size.height;
	ctx.fillStyle = 'orange';
	
	ctx.drawImage(datas.views[datas.view].hector, 0, 0, datas.size.width, datas.size.height);
	
	
	var ctxData = ctx.getImageData(0, 0, datas.size.width, datas.size.height);
	
	for(var k in datas.views[datas.view].segments) {
		var id = datas.views[datas.view].segments[k];
		var segment = datas.segments[id];
		var color = segment.color;
		if(id == datas.segment && datas.blink == 0) {
			color = {r: 255 - color.r, g: 255 - color.g, b: 255 - color.b};
		}
		printColor(ctxData, segment.image, color.r, color.g, color.b);
	}
	
	ctx.putImageData(ctxData, 0, 0);
	
}

function printColor(ctxData, image, r, g, b) {
	var myCanvas = document.createElement("canvas");
	var myCanvasContext = myCanvas.getContext("2d");
	
	myCanvas.width = datas.size.width;
	myCanvas.height = datas.size.height;
	
	myCanvasContext.drawImage(image, 0, 0, datas.size.width, datas.size.height);
	
	var imageData = myCanvasContext.getImageData(0,0, datas.size.width, datas.size.height);
	
	for(var i=0; i<imageData.width;i++) {
		for(var j=0; j<imageData.height ; j++) {
			var index = (j*4)*imageData.width+(i*4);
			
			var red = imageData.data[index];
			var green = imageData.data[index+1];
			var blue = imageData.data[index+2];
			var alpha = imageData.data[index+3];
			
			var gris = (red + green + blue) / (3 * 255);
			
			
			red = 255 * gris + (1 - gris) * r;
			green = 255 * gris + (1 - gris) * g;
			blue = 255 * gris + (1 - gris) * b;
			alpha = alpha/255;
			
			// Calcul
			red = alpha * red + (1 - alpha) * ctxData.data[index + 0];
			green = alpha * green + (1 - alpha) * ctxData.data[index + 1];
			blue = alpha * blue + (1 - alpha) * ctxData.data[index + 2];
			
			ctxData.data[index] = red;
			ctxData.data[index+1] = green;
			ctxData.data[index+2] = blue;
			ctxData.data[index+3] = ctxData.data[index+3];
			
		}
	}
	
}


</script>