# Customer Due Amount Complete Implementation

## Problem Statement
Customer due amount calculation was not working properly. Need to implement:
1. `current_due_amount = last_due_amount - opening_advance_amount`
2. When customer makes a sale, add sale due to customer's current due
3. When customer makes payment, reduce from customer's current due
4. Show proper due amounts in frontend

## Complete Solution

### Backend Changes

#### 1. Database Migrations
- **Customer Table**: Added `current_due_amount` field
- **SaleInvoice Table**: Added `customer_previous_due` and `customer_current_due` fields

#### 2. Customer Model (`app/Models/Customer.php`)
- Added `current_due_amount` to fillable and casts
- Added calculation methods

#### 3. Customer Controller (`app/Http/Controllers/CustomerController.php`)
- **Create Customer**: Calculate `current_due_amount = last_due_amount - opening_advance_amount`
- **Update Customer**: Recalculate `current_due_amount` when due/advance amounts change
- **Get Customers**: Include `currentDueAmount` in API response

#### 4. SaleInvoice Controller (`app/Http/Controllers/SaleInvoiceController.php`)
- **Create Sale**: Add sale due amount to customer's `current_due_amount`
- Store customer previous and current due in sale invoice

#### 5. PaymentSaleInvoice Controller (`app/Http/Controllers/PaymentSaleInvoiceController.php`)
- **Make Payment**: Reduce payment amount from customer's `current_due_amount`
- Update customer due amount after payment

### Frontend Changes

#### 1. Customer Slice (`src/redux/rtk/features/customer/customerSlice.js`)
- Added `current_due_amount` calculation in addCustomer and updateCustomer

#### 2. AddCustomer Component (`src/components/customer/AddCustomer.jsx`)
- Real-time calculation display
- Shows current due as user types

#### 3. UpdateCustomer Component (`src/components/customer/UpdateCustomer.jsx`)
- Real-time calculation display during editing

#### 4. GetAllCustomer Component (`src/components/customer/GetAllCustomer.jsx`)
- Updated Due Amount column to use `currentDueAmount`
- Shows "Balanced", "Due: Rs X", or "Advance: Rs X"

#### 5. Sale Components (`src/components/sale/AddSale.jsx`)
- Updated to use `current_due_amount` from customer
- Shows breakdown: Previous Due + Current Sale Due = Total Due

## How It Works

### Example Scenario:
1. **Customer Creation**:
   - Last Due: 50000 Rs
   - Opening Advance: 10000 Rs
   - **Current Due**: 50000 - 10000 = 40000 Rs

2. **New Sale**:
   - Sale Amount: 5000 Rs
   - Customer pays: 0 Rs
   - Sale Due: 5000 Rs
   - **New Customer Due**: 40000 + 5000 = 45000 Rs

3. **Payment**:
   - Customer pays: 15000 Rs
   - **New Customer Due**: 45000 - 15000 = 30000 Rs

### Formula:
```
Initial: current_due_amount = last_due_amount - opening_advance_amount
After Sale: current_due_amount = current_due_amount + sale_due_amount
After Payment: current_due_amount = current_due_amount - payment_amount
```

## Installation Steps

### 1. Run Migrations:
```bash
cd d:\xampp\htdocs\SPMURI_BACKEND
run_all_customer_due_migrations.bat
```

### 2. Test Frontend:
```bash
cd c:\SPMURI_FRONTEND
npm start
```

## Testing Checklist

### Backend Testing:
- [ ] Customer creation with due/advance amounts
- [ ] Customer update with due/advance amounts
- [ ] Customer list API returns `currentDueAmount`
- [ ] Sale creation updates customer due
- [ ] Payment reduces customer due

### Frontend Testing:
- [ ] Customer add form shows real-time calculation
- [ ] Customer edit form shows real-time calculation
- [ ] Customer list shows proper due amounts
- [ ] Sale form shows customer existing due
- [ ] Sale form calculates total due correctly

## API Endpoints Affected

### Customer APIs:
- `POST /customer` - Creates customer with calculated current_due_amount
- `PUT /customer/{id}` - Updates customer with recalculated current_due_amount
- `GET /customer` - Returns customers with currentDueAmount field

### Sale APIs:
- `POST /sale-invoice` - Updates customer current_due_amount after sale
- `POST /payment-sale-invoice` - Reduces customer current_due_amount after payment

## Database Schema Changes

### Customer Table:
```sql
ALTER TABLE customer ADD COLUMN current_due_amount DECIMAL(15,2) DEFAULT 0;
```

### SaleInvoice Table:
```sql
ALTER TABLE saleinvoice ADD COLUMN customer_previous_due DECIMAL(10,2) DEFAULT 0;
ALTER TABLE saleinvoice ADD COLUMN customer_current_due DECIMAL(10,2) DEFAULT 0;
```

## Success Criteria
✅ Customer due amount calculated correctly: `last_due_amount - opening_advance_amount`
✅ Sale due amount added to customer current due
✅ Payment amount reduced from customer current due
✅ Frontend shows proper due amounts in customer list
✅ Sale form includes customer existing due in total calculation
✅ Real-time calculation in customer add/edit forms