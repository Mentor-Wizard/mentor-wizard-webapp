-- Grant CREATEDB permission for parallel testing
-- Laravel's ParallelTesting creates databases: {DB_DATABASE}_test_{token}
ALTER USER test_mw_user CREATEDB;
