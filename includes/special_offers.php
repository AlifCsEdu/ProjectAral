<?php
class SpecialOffers {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getActiveOffers() {
        $query = "SELECT * FROM special_offers 
                 WHERE start_date <= CURRENT_DATE 
                 AND end_date >= CURRENT_DATE 
                 AND is_active = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewUserOffer() {
        $query = "SELECT * FROM special_offers 
                 WHERE is_new_user_only = TRUE 
                 AND start_date <= CURRENT_DATE 
                 AND end_date >= CURRENT_DATE 
                 AND is_active = TRUE 
                 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function validateOffer($code, $user_id, $order_total) {
        $query = "SELECT * FROM special_offers 
                 WHERE code = :code 
                 AND start_date <= CURRENT_DATE 
                 AND end_date >= CURRENT_DATE 
                 AND is_active = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            return ['valid' => false, 'message' => 'Invalid or expired offer code'];
        }

        // Check minimum order amount
        if ($order_total < $offer['minimum_order']) {
            return [
                'valid' => false, 
                'message' => "Minimum order amount of ₱" . number_format($offer['minimum_order'], 2) . " required"
            ];
        }

        // Check if new user only
        if ($offer['is_new_user_only']) {
            $order_query = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = :user_id";
            $order_stmt = $this->conn->prepare($order_query);
            $order_stmt->bindParam(':user_id', $user_id);
            $order_stmt->execute();
            $result = $order_stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['order_count'] > 0) {
                return ['valid' => false, 'message' => 'This offer is for new users only'];
            }
        }

        return [
            'valid' => true,
            'discount_percentage' => $offer['discount_percentage'],
            'message' => 'Offer applied successfully'
        ];
    }
}
?>
