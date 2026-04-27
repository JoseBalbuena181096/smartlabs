<?php include __DIR__ . '/../layout/header.php'; ?>

<div class="center-block w-xxl w-auto-xs p-y-md">
  <div class="p-a-md box-color r box-shadow-z1 text-color m-a">
    <div class="m-b text-sm text-primary _600" style="font-size: 16px">
      Registrar nuevo usuario administrativo SMARTLABS
    </div>
    <form method="post" name="form">
      <input type="hidden" name="_csrf" value="<?= Controller::e($csrf ?? '') ?>">
      <div class="md-form-group float-label">
        <input name="email" type="email" class="md-input" value="<?= Controller::e($email ?? '') ?>" required>
        <label>Email</label>
      </div>
      <div class="md-form-group float-label">
        <input name="password" type="password" class="md-input" minlength="8" required>
        <label>Contraseña (mínimo 8 caracteres)</label>
      </div>
      <div class="md-form-group float-label">
        <input name="confirm_password" type="password" class="md-input" minlength="8" required>
        <label>Confirmar contraseña</label>
      </div>
      <button type="submit" class="btn primary btn-block p-x-md">Crear usuario</button>
    </form>

    <?php if (!empty($msg)): ?>
      <div style="color:red" class="m-t">
        <?= Controller::e($msg) ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="p-v-lg text-center">
    <div><a href="/Dashboard" class="text-primary _600">← Volver al dashboard</a></div>
  </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
