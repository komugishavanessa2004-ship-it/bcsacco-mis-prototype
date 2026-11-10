<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'member') {
        $no = 'M-' . random_int(1000, 9999);
        $stmt = $pdo->prepare('INSERT INTO members (member_no, full_name, phone, nin, joined_on) VALUES (?,?,?,?,CURDATE())');
        $stmt->execute([$no, $_POST['name'], $_POST['phone'], $_POST['nin']]);
    }
    if ($action === 'cash') {
        $amount = (float) $_POST['amount'];
        $kind = $_POST['kind'];
        $id = (int) $_POST['member_id'];
        if ($kind === 'deposit') {
            $pdo->prepare('UPDATE members SET savings = savings + ? WHERE id = ?')->execute([$amount, $id]);
        } elseif ($kind === 'share') {
            $pdo->prepare('UPDATE members SET shares = shares + ? WHERE id = ?')->execute([$amount, $id]);
        } elseif ($kind === 'withdrawal') {
            $pdo->prepare('UPDATE members SET savings = GREATEST(0, savings - ?) WHERE id = ?')->execute([$amount, $id]);
        }
        $pdo->prepare('INSERT INTO transactions (member_id, kind, amount, posted_on, note) VALUES (?,?,?,CURDATE(),?)')
            ->execute([$id, $kind, $amount, $kind]);
    }
    if ($action === 'loan') {
        $no = 'L-' . random_int(2000, 9999);
        $principal = (float) $_POST['principal'];
        $pdo->prepare('INSERT INTO loans (loan_no, member_id, purpose, principal, months, guarantor, outstanding) VALUES (?,?,?,?,?,?,?)')
            ->execute([$no, (int) $_POST['member_id'], $_POST['purpose'], $principal, (int) $_POST['months'], $_POST['guarantor'], $principal]);
    }
    if ($action === 'decide') {
        $approve = ($_POST['decision'] ?? '') === 'approve';
        $loanId = (int) $_POST['loan_id'];
        if ($approve) {
            $loan = $pdo->query('SELECT member_id, principal FROM loans WHERE id = ' . $loanId)->fetch(PDO::FETCH_ASSOC);
            $pdo->prepare("UPDATE loans SET status='active', disbursed_on=CURDATE(), outstanding=principal WHERE id=? AND status='pending'")->execute([$loanId]);
            if ($loan) {
                $pdo->prepare("INSERT INTO transactions (member_id, kind, amount, posted_on, note) VALUES (?, 'disbursement', ?, CURDATE(), ?)")
                    ->execute([$loan['member_id'], $loan['principal'], 'Loan approved']);
            }
        } else {
            $pdo->prepare("UPDATE loans SET status='rejected', outstanding=0 WHERE id=? AND status='pending'")->execute([$loanId]);
        }
    }
    header('Location: index.php');
    exit;
}

$members = $pdo->query('SELECT * FROM members ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$loans = $pdo->query('SELECT loans.*, members.full_name FROM loans JOIN members ON members.id = loans.member_id ORDER BY loans.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$savings = $pdo->query('SELECT COALESCE(SUM(savings),0) s, COALESCE(SUM(shares),0) h, COUNT(*) c FROM members')->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bombo Community SACCO</title>
  <style>
    body { font-family: Georgia, serif; background: #f6f1e6; color: #12261c; margin: 0; }
    header { background: #0f3d28; color: #f6f1e6; padding: 16px 24px; }
    main { padding: 16px 24px; max-width: 980px; }
    form, table { background: #efe7d6; padding: 12px; margin: 12px 0; }
    input, select, button { font: inherit; padding: 8px; margin: 4px 0; }
    button { background: #1b6b43; color: #f6f1e6; border: 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { text-align: left; padding: 6px; border-bottom: 1px solid #d7ccb6; }
  </style>
</head>
<body>
<header><h1>Bombo Community SACCO</h1><p>PHP + MySQL prototype. Import schema.sql first. Edit config.php for your MySQL user.</p></header>
<main>
  <p>Members: <?= (int) $savings['c'] ?> · Savings: UGX <?= number_format((float) $savings['s']) ?> · Shares: UGX <?= number_format((float) $savings['h']) ?></p>
  <h2>Register</h2>
  <form method="post">
    <input type="hidden" name="action" value="member">
    <input name="name" placeholder="Full name" required>
    <input name="phone" placeholder="Phone" required>
    <input name="nin" placeholder="National ID">
    <button>Save member</button>
  </form>
  <h2>Savings</h2>
  <form method="post">
    <input type="hidden" name="action" value="cash">
    <select name="member_id"><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option><?php endforeach; ?></select>
    <input name="amount" type="number" min="1" step="1" required>
    <select name="kind"><option>deposit</option><option>withdrawal</option><option>share</option></select>
    <button>Post</button>
  </form>
  <h2>Loan</h2>
  <form method="post">
    <input type="hidden" name="action" value="loan">
    <select name="member_id"><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option><?php endforeach; ?></select>
    <input name="purpose" placeholder="Purpose" required>
    <input name="principal" type="number" min="1" required>
    <input name="months" type="number" min="1" value="4" required>
    <input name="guarantor" placeholder="Guarantor" required>
    <button>Apply</button>
  </form>
  <table>
    <tr><th>Loan</th><th>Member</th><th>Outstanding</th><th>Status</th><th></th></tr>
    <?php foreach ($loans as $loan): ?>
      <tr>
        <td><?= htmlspecialchars($loan['loan_no']) ?></td>
        <td><?= htmlspecialchars($loan['full_name']) ?></td>
        <td><?= number_format((float) $loan['outstanding']) ?></td>
        <td><?= htmlspecialchars($loan['status']) ?></td>
        <td>
          <?php if ($loan['status'] === 'pending'): ?>
            <form method="post">
              <input type="hidden" name="action" value="decide">
              <input type="hidden" name="loan_id" value="<?= (int) $loan['id'] ?>">
              <button name="decision" value="approve">Approve</button>
              <button name="decision" value="reject">Reject</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</main>
</body>
</html>
