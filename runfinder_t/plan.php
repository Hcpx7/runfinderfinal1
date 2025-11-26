<?php
// plan.php - Stylized Plans page (no external API). Payment simulated.
// Replace your existing plan.php with this file (server-side logic remains simulated).
require_once __DIR__ . '/includes/header.php';
global $pdo;
$me = current_user();
if (!$me) { header('Location: login.php'); exit; }

$messages = [];

// Cancel plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $stmt = $pdo->prepare("UPDATE users SET plan_paid = 0, plan_type = NULL, plan_expires = NULL WHERE id = ?");
    $stmt->execute([$me['id']]);
    $me = current_user();
    $messages[] = "Plano cancelado.";
    header('Location: plan.php'); exit;
}

// Process subscription (simulated). Sanitize input minimal.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe') {
    $plan = $_POST['plan'] ?? '';
    $card_name = trim($_POST['card_name'] ?? '');
    $card_number = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $card_exp = trim($_POST['card_exp'] ?? '');
    $card_cvv = trim($_POST['card_cvv'] ?? '');

    // Basic validation
    if (!in_array($plan, ['mensal','semestral','anual'])) $messages[] = "Plano inválido.";
    if (!$card_name || strlen($card_number) < 12 || !$card_exp || strlen($card_cvv) < 3) $messages[] = "Dados do cartão inválidos (simulação). Preencha corretamente.";

    if (empty($messages)) {
        $now = new DateTimeImmutable('now');
        if ($plan === 'mensal') {
            $expires = $now->modify('+30 days');
            $price = 199.90;
        } elseif ($plan === 'semestral') {
            $expires = $now->modify('+183 days');
            $price = 999.90;
        } else {
            $expires = $now->modify('+365 days');
            $price = 1999.90;
        }

        // Simulate payment success (DO NOT store card data)
        $stmt = $pdo->prepare("UPDATE users SET plan_paid = 1, plan_type = ?, plan_expires = ? WHERE id = ?");
        $stmt->execute([$plan, $expires->format('Y-m-d'), $me['id']]);

        $me = current_user();
        $messages[] = "Pagamento simulado aprovado. Plano '{$plan}' ativo até " . $expires->format('d/m/Y') . ".";
        header('Location: plan.php'); exit;
    }
}

// Display
?>
<h2>Planos RunFider</h2>

<?php foreach($messages as $m): ?>
  <div class="card small" style="margin-bottom:12px;color:#ffdfdf;"><?php echo esc($m); ?></div>
<?php endforeach; ?>

<div class="card plans-section">
  <div class="plans-header">
    <div>
      <h3 style="margin:0 0 6px 0">Planos RunFider</h3>
      <div class="small" style="color:var(--muted)">Escolha um plano e preencha os dados do cartão (simulação). Não são gravados dados sensíveis.</div>
    </div>
    <?php if (!empty($me['plan_paid'])): ?>
      <div class="center" style="text-align:right">
        <div class="price-pill">Plano ativo: <?php echo esc($me['plan_type'] ?? '—'); ?></div>
        <div class="small" style="margin-top:6px">Válido até: <?php echo !empty($me['plan_expires']) ? date('d/m/Y', strtotime($me['plan_expires'])) : '—'; ?></div>
        <form method="post" style="margin-top:10px">
          <input type="hidden" name="action" value="cancel">
          <button class="btn" type="submit" onclick="return confirm('Deseja realmente cancelar o plano?')">Cancelar plano</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($me['plan_paid'])): ?>
    <!-- If active, don't show other plan options -->
    <div style="margin-top:12px;">
      <p class="small">Seu plano está ativo. Se deseja trocar de plano, cancele e selecione outro.</p>
    </div>
  <?php else: ?>
    <div class="plans-grid" style="margin-top:14px;">
      <!-- Mensal -->
      <div class="plan-card">
        <div class="plan-title">Mensal</div>
        <div class="plan-price">R$ <strong>199,90</strong> / mês</div>
        <div class="price-pill">Melhor para testar</div>

        <form method="post" class="plan-form" onsubmit="return validateCard(this);">
          <input type="hidden" name="action" value="subscribe">
          <input type="hidden" name="plan" value="mensal">

          <div class="form-row">
            <label>Nome no cartão</label>
            <input class="input-small" type="text" name="card_name" placeholder="Nome impresso" required>
          </div>

          <div class="form-row">
            <label>Número do cartão</label>
            <input class="input-small" type="text" name="card_number" placeholder="xxxx xxxx xxxx xxxx" maxlength="23" inputmode="numeric" required>
          </div>

          <div class="plan-form row">
            <div class="col">
              <div class="form-row">
                <label>Validade (MM/AA)</label>
                <input class="input-small" type="text" name="card_exp" placeholder="MM/AA" maxlength="5" required>
              </div>
            </div>
            <div style="width:140px;">
              <div class="form-row">
                <label>CVV</label>
                <input class="input-small" type="text" name="card_cvv" maxlength="4" inputmode="numeric" required>
              </div>
            </div>
          </div>

          <div class="plan-cta">
            <button class="btn" type="submit">Pagar R$ 199,90</button>
          </div>
        </form>
      </div>

      <!-- Semestral -->
      <div class="plan-card">
        <div class="plan-title">Semestral</div>
        <div class="plan-price">R$ <strong>999,90</strong> / 6 meses</div>
        <div class="price-pill">Para organizadores regulares</div>

        <form method="post" class="plan-form" onsubmit="return validateCard(this);">
          <input type="hidden" name="action" value="subscribe">
          <input type="hidden" name="plan" value="semestral">

          <div class="form-row">
            <label>Nome no cartão</label>
            <input class="input-small" type="text" name="card_name" required>
          </div>

          <div class="form-row">
            <label>Número do cartão</label>
            <input class="input-small" type="text" name="card_number" maxlength="23" inputmode="numeric" required>
          </div>

          <div class="plan-form row">
            <div class="col">
              <div class="form-row">
                <label>Validade (MM/AA)</label>
                <input class="input-small" type="text" name="card_exp" maxlength="5" required>
              </div>
            </div>
            <div style="width:140px;">
              <div class="form-row">
                <label>CVV</label>
                <input class="input-small" type="text" name="card_cvv" maxlength="4" inputmode="numeric" required>
              </div>
            </div>
          </div>

          <div class="plan-cta">
            <button class="btn" type="submit">Pagar R$ 999,90</button>
          </div>
        </form>
      </div>

      <!-- Anual -->
      <div class="plan-card">
        <div class="plan-title">Anual</div>
        <div class="plan-price">R$ <strong>1.999,90</strong> / ano</div>
        <div class="price-pill">Melhor custo-benefício</div>

        <form method="post" class="plan-form" onsubmit="return validateCard(this);">
          <input type="hidden" name="action" value="subscribe">
          <input type="hidden" name="plan" value="anual">

          <div class="form-row">
            <label>Nome no cartão</label>
            <input class="input-small" type="text" name="card_name" required>
          </div>

          <div class="form-row">
            <label>Número do cartão</label>
            <input class="input-small" type="text" name="card_number" maxlength="23" inputmode="numeric" required>
          </div>

          <div class="plan-form row">
            <div class="col">
              <div class="form-row">
                <label>Validade (MM/AA)</label>
                <input class="input-small" type="text" name="card_exp" maxlength="5" required>
              </div>
            </div>
            <div style="width:140px;">
              <div class="form-row">
                <label>CVV</label>
                <input class="input-small" type="text" name="card_cvv" maxlength="4" inputmode="numeric" required>
              </div>
            </div>
          </div>

          <div class="plan-cta">
            <button class="btn" type="submit">Pagar R$ 1.999,90</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
// Client-side formatting & validation helpers for card inputs (ui only, no real validation)
function formatCardNumber(input){
  let v = input.value.replace(/\D/g,'').slice(0,19);
  v = v.replace(/(\d{4})(?=\d)/g, '$1 ');
  input.value = v;
}
function formatExp(input){
  let v = input.value.replace(/\D/g,'').slice(0,4);
  if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2,4);
  input.value = v;
}

document.querySelectorAll('input[name="card_number"]').forEach(i=>{
  i.addEventListener('input', ()=>formatCardNumber(i));
});
document.querySelectorAll('input[name="card_exp"]').forEach(i=>{
  i.addEventListener('input', ()=>formatExp(i));
});

// Validate minimal fields before submit
function validateCard(form){
  const name = form.card_name.value.trim();
  const number = form.card_number.value.replace(/\s/g,'');
  const exp = form.card_exp.value.trim();
  const cvv = form.card_cvv.value.trim();
  if (!name || number.length < 12 || !/^\d{2}\/\d{2}$/.test(exp) || cvv.length < 3) {
    alert('Preencha corretamente os dados do cartão (simulação).');
    return false;
  }
  return true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>