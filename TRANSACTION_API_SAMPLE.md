# Transaction API Output Sample

## Sample JSON Response from `/USER_API/transactions.php`

```json
{
  "status": "success",
  "transactions": [
    {
      "transaction_id": 1,
      "user_id": 123,
      "booking_id": 456,
      "transaction_code": "TRX-2026-001",
      "transaction_date": "2026-03-18 14:30:00",
      "date": "2026-03-18",
      "description": "Lawn Maintenance Service",
      "type": "Payment",
      "status": "Completed",
      "amount": 150.00,
      "notes": "Monthly lawn care service",
      "created_at": "2026-03-18 14:35:00",
      "user_record_id": 123,
      "avatar_url": "/uploads/users/avatar_123.jpg",
      "full_name": "John Doe",
      "user_email": "john.doe@example.com",
      "user_phone": "+1234567890"
    },
    {
      "transaction_id": 2,
      "user_id": 124,
      "booking_id": 457,
      "transaction_code": "TRX-2026-002",
      "transaction_date": "2026-03-17 10:15:00",
      "date": "2026-03-17",
      "description": "Garden Design Consultation",
      "type": "Payment",
      "status": "Pending",
      "amount": 75.00,
      "notes": "Initial consultation fee",
      "created_at": "2026-03-17 10:20:00",
      "user_record_id": 124,
      "avatar_url": "/uploads/users/default-avatar.png",
      "full_name": "Jane Smith",
      "user_email": "jane.smith@example.com",
      "user_phone": "+0987654321"
    }
  ],
  "summary": {
    "revenue_this_month": 2250.00,
    "expenses_this_month": 850.00,
    "net_profit_this_month": 1400.00,
    "transactions_this_month": 15,
    "revenue_growth": 12.5,
    "expense_growth": -5.2,
    "transactions_growth": 8.7,
    "profit_growth": 18.3,
    "total": 145,
    "filtered": 145
  }
}
```

## Data Structure Explanation

### Transaction Object Fields
- **transaction_id**: Primary key of the transaction (integer)
- **user_id**: Foreign key to users table (integer)
- **booking_id**: Associated booking ID (integer, nullable)
- **transaction_code**: Human-readable transaction code (string)
- **transaction_date**: Full timestamp from database (string)
- **date**: Formatted date (YYYY-MM-DD) for frontend display
- **description**: Transaction description (string)
- **type**: Transaction type (Payment, Expense, Refund, etc.)
- **status**: Transaction status (Pending, Completed, Failed, etc.)
- **amount**: Transaction amount as float
- **notes**: Additional notes (string, nullable)
- **created_at**: Record creation timestamp (string)

### User Data Fields (from JOIN)
- **user_record_id**: User table primary key (integer)
- **avatar_url**: Path to user avatar image (string, with default fallback)
- **full_name**: Concatenated user full name (string, with fallbacks)
- **user_email**: User email address (string)
- **user_phone**: User phone number (string)

### Summary Object Fields
- **revenue_this_month**: Current month revenue (float)
- **expenses_this_month**: Current month expenses (float)
- **net_profit_this_month**: Current month profit (float)
- **transactions_this_month**: Current month transaction count (integer)
- **revenue_growth**: Revenue growth percentage (float)
- **expense_growth**: Expense growth percentage (float)
- **transactions_growth**: Transaction count growth percentage (float)
- **profit_growth**: Profit growth percentage (float)
- **total**: Total transactions in database (integer)
- **filtered**: Filtered transaction count (integer, equals total when no filters)

## API Parameters

### Query Parameters
- **page**: Page number (default: 1)
- **status**: Filter by status (all, pending, completed, etc.)
- **transactionType**: Filter by transaction type (all, payment, expense, etc.)
- **category**: Filter by category (all, lawn maintenance, etc.)
- **from**: Filter by start date (YYYY-MM-DD)
- **to**: Filter by end date (YYYY-MM-DD)
- **order**: Sort order (newest, oldest, amount_high, amount_low)
- **booking_id**: Get transactions for specific booking only

### Security Features
- PDO prepared statements prevent SQL injection
- User permission validation
- Proper error handling with appropriate HTTP status codes
- Data sanitization and type casting
