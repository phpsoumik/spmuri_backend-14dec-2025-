<?php
// Database connection
// $conn = new mysqli("localhost", "root", "", "altius");

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Initialize variables for date filter
$date_filter = "";
$from = '';
$to = '';
if (isset($_POST['button1']) && isset($_POST['button2'])) {
    $from = $_POST['button1'];
    $to = $_POST['button2'];
    $date_filter = "WHERE po.date_created BETWEEN '$from' AND '$to'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(DISTINCT po.id) as total 
FROM po_list po 
INNER JOIN supplier_list s ON po.supplier_id = s.id 
LEFT JOIN doctor_list d ON s.referring_doctor = d.doctor_id 
LEFT JOIN test_upload ON test_upload.invoice_id = po.id 
LEFT JOIN order_items oi ON po.id = oi.po_id 
LEFT JOIN item_list il ON oi.item_id = il.id 
$date_filter";

$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query with pagination
$query = "SELECT 
    po.date_created AS creation, 
    po.total, 
    po.advance AS adv, 
    po.po_no, 
    s.patient_name AS sname, 
    s.patient_phone AS phone, 
    s.patient_age AS age, 
    s.patient_gender AS gender, 
    po.id AS order_id, 
    GROUP_CONCAT(il.name) AS item_names, 
    d.doctor_name AS doctor_name 
FROM po_list po 
INNER JOIN supplier_list s ON po.supplier_id = s.id 
LEFT JOIN doctor_list d ON s.referring_doctor = d.doctor_id 
LEFT JOIN test_upload ON test_upload.invoice_id = po.id 
LEFT JOIN order_items oi ON po.id = oi.po_id 
LEFT JOIN item_list il ON oi.item_id = il.id 
$date_filter
GROUP BY po.id 
ORDER BY po.id DESC 
LIMIT $offset, $records_per_page";

$qry = $conn->query($query);

if (!$qry) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders</title>
    <link rel="stylesheet" href="#"> <!-- Include your CSS file -->
    <style>
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            margin: 0 4px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #007bff;
        }
        .pagination a:hover {
            background-color: #e9ecef;
        }
        .pagination .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .pagination .disabled {
            color: #6c757d;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">All Invoices</h3>
        <div class="card-tools">
            <a href="?page=purchase_orders/manage_po" class="btn btn-flat btn-primary">
                <span class="fas fa-plus"></span> Create New
            </a>
        </div>
        <div class="card-tools mr-4">
            <a href="<?= base_url ?>admin/reports/export_excel.php?fromDate=<?php echo $from?>&toDate=<?php echo $to?>" class="btn btn-flat btn-primary">
                <span class="fas fa-file-excel"></span> Export Excel Sheet
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <!-- Pagination Info -->
            <div class="mb-3">
                <p>Showing <?= ($offset + 1) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries</p>
            </div>
            
            <div class="container-fluid">
                <table class="table table-hover table-striped">
                    <colgroup>
                        <col width="5%">
                        <col width="15%">
                        <col width="15%">
                        <col width="20%">
                        <col width="10%">
                        <col width="15%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr class="bg-navy disabled">
                            <th>#</th>
                            <th style="text-align:center">Date Created</th>
                            <th style="text-align:center">Invoice No</th>
                            <th style="text-align:center">Doctor Name</th>
                            <th style="text-align:center">Patient</th>
                            <th style="text-align:center">Age</th>
                            <th style="text-align:center">Gender</th>
                            <th style="text-align:center">Test Name</th>
                            <th style="text-align:center">Patient Phone</th>
                            <th style="text-align:center">Total</th>
                            <th style="text-align:center">Paid</th>
                            <th style="text-align:center">Due</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = $offset + 1;
                        while ($row = $qry->fetch_assoc()) {
                            // Calculate due amount
                            $total = intval(str_replace(',', '', $row['total']));
                            $adv = intval(str_replace(',', '', $row['adv']));
                            $due_amount = $total - $adv;
                        ?>
                            <tr style="text-align:center">
                                <td><?= $i++ ?></td>
                                <td>
                                    <?php
                                    $d = $row['creation'];
                                    $datetime = new DateTime($d, new DateTimeZone('UTC'));
                                    $datetime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                    echo $datetime->format('d-m-Y H:i:s');
                                    ?>
                                </td>
                                <td><?= $row['po_no'] ?></td>
                                <td><?= isset($row['doctor_name']) ? htmlspecialchars($row['doctor_name']) : '' ?></td>
                                <td><?= $row['sname'] ?></td>
                                <td><?= htmlspecialchars($row['age']) ?></td>
                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td><?= $row['item_names'] ?></td>
                                <td><?= $row['phone'] ?></td>
                                <td><?= number_format(floatval(str_replace(',', '', $row['total'])), 2) ?></td>
                                <td><?= number_format(floatval(abs($due_amount)), 2) ?></td>
                                <td><?= number_format(floatval(str_replace(',', '', $adv)), 2) ?></td>
                                <td>
                                    <a href="?page=purchase_orders/view_po&id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Links -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- Previous Page -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($from) && !empty($to) ? '&button1='.$from.'&button2='.$to : '' ?>">&laquo; Previous</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Previous</span>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <a href="?page=1<?= !empty($from) && !empty($to) ? '&button1='.$from.'&button2='.$to : '' ?>">1</a>
                    <?php if ($start_page > 2): ?>
                        <span>...</span>
                    <?php endif;
                endif;

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= !empty($from) && !empty($to) ? '&button1='.$from.'&button2='.$to : '' ?>"><?= $i ?></a>
                    <?php endif;
                endfor;

                if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $total_pages ?><?= !empty($from) && !empty($to) ? '&button1='.$from.'&button2='.$to : '' ?>"><?= $total_pages ?></a>
                <?php endif; ?>

                <!-- Next Page -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($from) && !empty($to) ? '&button1='.$from.'&button2='.$to : '' ?>">Next &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>