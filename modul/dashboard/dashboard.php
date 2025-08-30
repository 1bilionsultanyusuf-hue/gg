<?php
$result = $koneksi->query("SELECT * FROM apps");
while($row = $result->fetch_assoc()){
    echo $row['name'] . "<br>";
}
?>
