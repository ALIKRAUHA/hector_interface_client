<?php

file_put_contents('last.txt', $_POST['datas']."\n", FILE_APPEND);

header('Location: index.php?saved=true&language=' . $_POST['language']);