<?= view('Platform\Views\public\_head', ['title' => 'Choose a workspace']) ?>
<div class="card" style="max-width:480px;margin:20px auto">
  <h1>Choose a workspace</h1>
  <p class="sub"><?= esc($email) ?> belongs to more than one workspace.</p>
  <div class="methods">
    <?php foreach ($items as $i): ?>
      <a class="method" href="<?= esc($i['url'], 'attr') ?>" style="text-decoration:none;color:inherit">
        <span><b><?= esc($i['name']) ?></b><small><?= esc($i['address']) ?></small></span><span class="btn sec">Open</span></a>
    <?php endforeach; ?>
  </div>
  <p class="mono" style="margin-top:16px">These links work for one minute.</p>
</div>
</div></body></html>
