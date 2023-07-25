<?php
session_start();
session_destroy();
echo "<script>alert('Berhasil logout dari LAPOR PAK! See you'); window.location='../index.php';</script>";