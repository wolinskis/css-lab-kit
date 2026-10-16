<?php require __DIR__.'/header.php'; ?>
<div class="card">
  <h2>Upload a report</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="file"><br><br><button>Upload</button>
  </form>
<?php
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['file'])) {
    // Insecure upload: no extension / content-type / content check. Saved under the web root.
    $name = basename($_FILES['file']['name']);
    $dest = __DIR__.'/uploads/'.$name;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        echo "<div class='muted'>Stored at <code>uploads/".htmlspecialchars($name)."</code> — "
            ."<a href='uploads/".rawurlencode($name)."'>open it</a></div>";
    } else { echo "<div class='muted'>Upload failed.</div>"; }
}
?>
</div>
<?php require __DIR__.'/footer.php';
