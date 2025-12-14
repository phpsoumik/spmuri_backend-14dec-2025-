-- Sale Return Adjustments Table
CREATE TABLE IF NOT EXISTS sale_return_adjustments (
    id VARCHAR(255) PRIMARY KEY,
    return_sale_invoice_id VARCHAR(255) NOT NULL,
    adjustment_type ENUM('cash_refund', 'product_exchange') NOT NULL,
    cash_refund_amount DECIMAL(10, 2) DEFAULT 0.00,
    exchange_product_id VARCHAR(255) NULL,
    exchange_quantity INT DEFAULT 0,
    exchange_bag DECIMAL(10, 2) DEFAULT 0.00,
    exchange_kg DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT NULL,
    status BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (return_sale_invoice_id) REFERENCES returnSaleInvoice(id) ON DELETE CASCADE,
    FOREIGN KEY (exchange_product_id) REFERENCES product(id) ON DELETE SET NULL
);

-- Add index for better performance
CREATE INDEX idx_sale_return_adjustments_return_invoice ON sale_return_adjustments(return_sale_invoice_id);
CREATE INDEX idx_sale_return_adjustments_type ON sale_return_adjustments(adjustment_type);
CREATE INDEX idx_sale_return_adjustments_status ON sale_return_adjustments(status);