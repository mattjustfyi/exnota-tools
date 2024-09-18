# Test Scenarios

## Duplicates

### Auth

- User not logged in to Notion
- Close window without completing or decline
- Notion user already has an account

### Choose database

- Getting database list fails once and recovers
- Getting database list fails and does not recover
- No databases found
- Desired database not found
- Desired database in another workspace
- Can't access another user's workspace

### Search database

- Database is empty
- Database has no duplicates
- Database rows with same titles but different cases not detected
- Database as thousands of entries with many, complex properties and many duplicates.
- Have lost database access
- Have lost Notion access
- Searching fails once and recovers
- Searching fails and does not recover
- Can't search another user's workspace

### Delete

- Row has already now been deleted
- Row has been moved and no longer have access
- Row has been moved out of database
- Have lost database access
- Have lost Notion access
- Deleting fails once and recovers
- Deleting fails and does not recover
- Can't delete from another user's database
