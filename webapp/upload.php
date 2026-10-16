<?php require __DIR__.'/header.php'; require __DIR__.'/levels.php'; ?>
<div class="card">
  <h2>Upload a report <span class="muted" style="font-size:.6em">(level: <?php echo lvl(); ?>)</span></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="level" value="<?php echo lvl(); ?>">
    <input type="file" name="file"><br><br><button>Upload</button>
  </form>
<?php
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['file'])) {
    $L = lvl();
    $name = basename($_FILES['file']['name']);
    $ctype = $_FILES['file']['type'] ?? '';
    $content = @file_get_contents($_FILES['file']['tmp_name']);
    $why = upload_reject($name, $ctype, $content, $L);   // escalating checks per level
    if ($why !== '') {
        echo "<div class='muted'>❌ Upload rejected (level $L): ".htmlspecialchars($why)."</div>";
    } else {
        $dest = __DIR__.'/uploads/'.$name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            echo "<div class='muted'>Stored at <code>uploads/".htmlspecialchars($name)."</code> — "
                ."<a href='uploads/".rawurlencode($name)."'>open it</a></div>";
        } else { echo "<div class='muted'>Upload failed.</div>"; }
    }
}
?>
</div>
<?php require __DIR__.'/footer.php';
