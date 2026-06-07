<template>
  <div class="forbidden">
    <div class="box">
      <div class="code">403</div>
      <h2>Akses Ditolak</h2>
      <p>Anda tidak punya izin untuk mengakses halaman ini.</p>
      <RouterLink to="/app/dashboard" class="back-btn">Kembali ke Dashboard</RouterLink>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.forbidden { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f9fafb; }
.box { text-align: center; }
.code { font-size: 72px; font-weight: 800; color: #dc2626; }
.box h2 { margin: 8px 0; color: #111827; }
.box p { color: #6b7280; margin-bottom: 20px; }
.back-btn { display: inline-block; padding: 10px 20px; background: #dc2626; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; }
</style>
