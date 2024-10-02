# Benchmarks

## Error 500 "Unsaved transactions: Unrecognized error."

- With 4 parallel workers we can get to 700-ish deletes quickly.
- However then the above 500 error starts being returned.
- Sparsely at first, increasing over time.
- It can even get to the stage where 0 deletes are successful, even after
  stopping all deletes for an hour or longer!!
- It may be possible to avoid that state by adding long delays, e.g. 10 seconds,
  when the errors start occuring.
- Important: The errors seem tied to a specific Notion user:
  - So if they try deletes on another database, the errors will still happen.
  - If another user tries, the errors won't happen.
  - The errors will still happen if the same user connects to another
    environment (e.g. errors happen on both local and prod).
- I have a hunch some deletes may succeed in spite of this error being returned,
  however I have not validated that.

## 21 Sep 2024

### Test data

Mockaroo schema: <https://www.mockaroo.com/schemas/648173>

### Results

29 Sep 2024 - Good performance, minimal errors.

Search 1000: 30s
Delete 500: 2m

Search 2000: 1m
Delete 1000: 5m

Search 4000:
Delete 2000:

Search 8000
Delete 4000:

Search 20000:
Delete 10000:
