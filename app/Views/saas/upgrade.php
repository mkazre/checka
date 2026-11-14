<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php /** @var array $module */ ?>
<div class="card" style="max-width:560px;margin:40px auto;text-align:center">
  <div class="card-b" style="padding:34px 28px">
    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ink-3)"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
    <h1 style="font-size:21px;margin:12px 0 8px"><?= esc($module['name']) ?> isn't in your package</h1>
    <p style="color:var(--ink-3);line-height:1.6;margin:0 0 18px"><?= esc($module['description']) ?></p>
    <a class="btn" href="<?= site_url('subscription') ?>">See upgrade options</a>
    <div style="margin-top:12px"><a href="<?= site_url('dashboard') ?>" style="font-size:13px">Back to dashboard</a></div>
  </div>
</div>
<?= $this->endSection() ?>
