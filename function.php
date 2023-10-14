<?php
require 'vendor/autoload.php'; // Load Composer autoloader
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connect to PlanetScale using credentials stored in environment variables
$mysqli = mysqli_init();
$mysqli->ssl_set(NULL, NULL, "/etc/ssl/certs/ca-certificates.crt", NULL, NULL);
$mysqli->real_connect($_ENV["DATABASE_HOST"], $_ENV["DATABASE_USERNAME"], $_ENV["DATABASE_PASSWORD"], $_ENV["DATABASE"]);

// Check connection
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

// Query to fetch list of tables
$query = "SHOW TABLES";
$result = $mysqli->query($query);

if ($result) {
    $tables = $result->fetch_all(MYSQLI_NUM);
    if (!empty($tables)) {
        echo "Tables in the database:\n";
        foreach ($tables as $table) {
            echo "- $table[0]\n";
        }
    } else {
        echo "No tables found in the database.\n";
    }
    $result->close();
} else {
    echo "Error fetching tables: " . $mysqli->error;
}

$mysqli->close();

function insertPengaduan($data) {
    global $conn;
    date_default_timezone_set('Asia/Jakarta');
    $id = $data['id'];
    $np = htmlspecialchars($data["nama"]);
    $jp = htmlspecialchars($data["jabatan"]);
    $dp = htmlspecialchars($data["dept"]);
    $nb = htmlspecialchars($data["nama_barang"]);
    $ket = mysqli_real_escape_string($conn, $data["ket"]);
    $status = "Sedang diajukan";
    $ket_petugas = "-";
    $tgl_lapor = date("Y-m-d");
    $gambar = uploadGambarPengaduan();
    if(!$gambar){
        return false;
    }
    
    mysqli_query($conn, "INSERT INTO pengaduan VALUES('$id', '$np', '$jp', '$dp', '$nb', '$ket', '$status', '$ket_petugas', '$tgl_lapor','$gambar')");
    return mysqli_affected_rows($conn);
}

function uploadGambarPengaduan(){
    $namaFile = $_FILES['gambar']['name'];
	$ukuranFile = $_FILES['gambar']['size'];
	$error = $_FILES['gambar']['error'];
	$tmpName = $_FILES['gambar']['tmp_name'];

	// cek apakah tidak ada gambar yang diupload
	if( $error === 4 ) {
		echo "<script>
				alert('pilih gambar terlebih dahulu!');
			  </script>";
		return false;
	}

	// cek apakah yang diupload adalah gambar
	$ekstensiGambarValid = ['jpg', 'jpeg', 'png'];
	$ekstensiGambar = explode('.', $namaFile);
	$ekstensiGambar = strtolower(end($ekstensiGambar));
	if( !in_array($ekstensiGambar, $ekstensiGambarValid) ) {
		echo "<script>
				alert('yang anda upload bukan gambar!');
			  </script>";
		return false;
	}

	// cek jika ukurannya terlalu besar
	if( $ukuranFile > 1000000 ) {
		echo "<script>
				alert('ukuran gambar terlalu besar!');
			  </script>";
		return false;
	}

	// lolos pengecekan, gambar siap diupload
	// generate nama gambar baru
	$namaFileBaru = uniqid();
	$namaFileBaru .= '.';
	$namaFileBaru .= $ekstensiGambar;

	move_uploaded_file($tmpName, 'public/img/pengaduan/' . $namaFileBaru);

	return $namaFileBaru;
}

function registrasi($data) {
    global $conn;

    $username = strtolower(stripslashes($data["username"]));
    $password = mysqli_real_escape_string($conn, $data["password"]);
    $name = htmlspecialchars($data["name"]);
    $nip = htmlspecialchars($data["nip"]);
    $img = "default.jpg";
    $status = "0";

    $cek = mysqli_query($conn, "SELECT username, user_id FROM user WHERE username = '$username' OR user_id = '$nip'");

    if (mysqli_fetch_assoc($cek)) {
        echo "<script>alert('Username $username or NIP $nip was already registered!');</script>";
        return false;
    }

    $password = password_hash($password, PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO user VALUES('$nip', '$username', '$password', '$name', '$img', '$status')");

    return mysqli_affected_rows($conn);
}

function updatePass($data) {
    global $conn;
    
    $id = $data['id'];
    $password_baru = mysqli_real_escape_string($conn, $data["password_baru"]);
    $password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
    mysqli_query($conn, "UPDATE user SET password='$password_baru' WHERE user_id='$id'"); 

    return mysqli_affected_rows($conn);
}

function updatePengaduan($data) {
    global $conn;
    
    $id = $data['id'];
    $status = $data['status'];
    $ket_petugas = $data['ket_petugas'];
    mysqli_query($conn, "UPDATE pengaduan SET status = '$status', ket_petugas='$ket_petugas' WHERE id='$id'"); 

    return mysqli_affected_rows($conn);
}

function updatePhoto($data) {
    global $conn;
    
    $id = $_SESSION['login']['user_id'];
        
        $rand = rand();
        $ekstensi =  array('png','jpg','jpeg');
        $filename = $_FILES['foto']['name'];
        $ukuran = $_FILES['foto']['size'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(!in_array($ext,$ekstensi) ) {
            echo "<script>alert('Ekstensi tidak diperbolehkan atau Anda belum memilih file apapun.'); window.location='profil.php';</script>";
        }else{
            if($ukuran < 2044070){		
                $xx = $rand.'_'.$filename;
                move_uploaded_file($_FILES['foto']['tmp_name'], '../assets/img/profile/'.$rand.'_'.$filename);

                mysqli_query($conn, "UPDATE user SET img = '$xx' WHERE user_id='$id'"); 
        
            } else {
                echo "<script>alert('Size file terlalu beasr! Size yang diperbolehkan tidak melebihi 2 MB.'); window.location='profil.php';</script>";
            }
        }
    return mysqli_affected_rows($conn);
}

function deleteUser($id) {
    global $conn;
    mysqli_query($conn, "DELETE FROM user WHERE user_id = '$id'");
    return mysqli_affected_rows($conn);
}

function deletePengaduan($id) {
    global $conn;
    mysqli_query($conn, "DELETE FROM pengaduan WHERE id = '$id'");
    return mysqli_affected_rows($conn);
}

function searchPengaduan($keyword) {
    global $conn;
    $data = mysqli_query($conn, "SELECT * FROM pengaduan WHERE id = '$keyword'");
    return mysqli_affected_rows($conn);
}

?>