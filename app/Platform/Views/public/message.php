<?= view('Platform\Views\public\_head', ['title' => $title]) ?>
<div class="card" style="max-width:540px;margin:20px auto">
  <h1><?= esc($title) ?></h1>
  <?php if (! empty($errors)): ?><div class="err"><?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?></div><?php endif; ?>
  <p class="sub"><?= $body ?></p>
  <?php if (! empty($back)): ?><button class="btn" onclick="history.back()">Go back</button><?php endif; ?>
  <?php if (! empty($link)): ?><a class="btn" href="<?= esc($link[0], 'attr') ?>"><?= esc($link[1]) ?></a><?php endif; ?>
</div>
</div></body></html>
