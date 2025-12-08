<?php
// Auto-ID generator for different tables
/*Usage:
    generateUserID($conn, 'admin');
    generateAddressID($conn);
    generateItemID($conn);
    generateCategoryID($conn);
    generateSubCategoryID($conn);
    generateOrderID($conn);
    generatePaymentID($conn);
    generateReviewID($conn);
*/

/*  USER ID (Admin / Customer)
    Prefix:
    A = Admin
    C = Customer*/
function generateUserID($conn, $role) {
    $prefix = ($role === 'admin') ? 'A' : 'C';
    return generateAutoID($conn, 'Users', 'userID', $prefix, 5);
}
/*  ADDRESS ID
    Prefix: AD  */
function generateAddressID($conn) {
    return generateAutoID($conn, 'UserAddress', 'addressID', 'AD', 5);
}
/*  ITEM ID
    Prefix: I   */
function generateItemID($conn) {
    return generateAutoID($conn, 'Items', 'itemID', 'I', 5);
}
/*  CATEGORY ID
    Prefix: CAT */
function generateCategoryID($conn) {
    return generateAutoID($conn, 'Category', 'categoryID', 'CAT', 5);
}
/*  SUBCATEGORY ID
    Prefix: SUB */
function generateSubCategoryID($conn) {
    return generateAutoID($conn, 'SubCategory', 'subCategoryID', 'SUB', 5);
}
/*  ORDER ID
    Prefix: O   */
function generateOrderID($conn) {
    return generateAutoID($conn, 'Orders', 'orderID', 'O', 5);
}
/*  PAYMENT ID
    Prefix: P   */
function generatePaymentID($conn) {
    return generateAutoID($conn, 'Payments', 'paymentID', 'P', 5);
}
/*  REVIEW ID
    Prefix: R   */
function generateReviewID($conn) {
    return generateAutoID($conn, 'Reviews', 'reviewID', 'R', 5);
}
//       Generic Auto-ID Generator        //
function generateAutoID($conn, $table, $column, $prefix, $padLength = 5) {
    $stmt = $conn->prepare("
        SELECT $column FROM $table
        WHERE $column LIKE ?
        ORDER BY $column DESC
        LIMIT 1
    ");
    $like = $prefix . '%';
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $lastID = $row[$column];
        $num = (int)preg_replace("/[^0-9]/", '', $lastID) + 1;
    } else {
        $num = 1;
    }

    return $prefix . str_pad($num, $padLength, '0', STR_PAD_LEFT);
}
?>
