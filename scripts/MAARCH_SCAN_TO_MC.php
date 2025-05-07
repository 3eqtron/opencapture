<?php
require_once '/opt/maarch/MaarchCapture/MaarchCapture.php';
$maarchCapture = new MaarchCapture();
$maarchCapture->runBatch('MAARCH_SCAN_TO_MC');
