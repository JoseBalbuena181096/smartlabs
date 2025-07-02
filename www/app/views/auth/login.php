<?php include __DIR__ . '/../layout/header.php'; ?>

<div class="center-block w-xxl w-auto-xs p-y-md">
  <div class="navbar">
    <div class="pull-center">
    </div>
  </div>
  <div class="p-a-md box-color r box-shadow-z1 text-color m-a">
    <img src="/assets/images/smartlabs.png" style="height: 180px;" alt="SMARTLABS">
    <div class="m-b text-sm text-primary _600" style="font-size: 16px">
      Iniciar sesión con tu cuenta SMARTLABS
    </div>
    <form method="post" name="form">
      <div class="md-form-group float-label">
        <input name="email" type="email" class="md-input" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
        <label>Email</label>
      </div>
      <div class="md-form-group float-label">
        <input name="password" type="password" class="md-input" required>
        <label>Contraseña</label>
      </div>
      <button type="submit" class="btn primary btn-block p-x-md">Iniciar Sesión</button>
    </form>

    <?php if (!empty($msg)): ?>
      <div style="color:red" class="m-t">
        <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="p-v-lg text-center">
    <div>¿No tienes una cuenta? <a href="/Auth/register" class="text-primary _600">Registrarse</a></div>
  </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?> 