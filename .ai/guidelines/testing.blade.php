## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
    - You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
    - Tests should test all of the happy paths, failure paths, and weird paths.
    - Tests live in the `tests/Feature` and `tests/Unit` directories.
    - Pest tests look and behave like this:
    <code-snippet name="Basic Pest Test Example" lang="php">
        it('is true', function () {
        expect(true)->toBeTrue();
        });
    </code-snippet>

    ### Running Tests
    - Always run tests in a Docker container.
    - Run the minimal number of tests using an appropriate filter before finalizing code edits.
    - To run all tests: `docker compose exec -it app php artisan test`.
    - To run all tests in a file: `docker compose exec -it app php artisan test tests/Feature/ExampleTest.php`.
    - To filter on a particular test name: `docker compose exec -it app php artisan test --filter=testName` (recommended after making a change to a related file).
    - When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.
    - After running tests run mutation tests: `docker compose exec app ./vendor/bin/pest --mutate --covered-only --parallel --min=100`.
    - After running mutation tests, run the test suite again to ensure everything is still passing.

    ### Pest Assertions
    - When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
    <code-snippet name="Pest Example Asserting postJson Response" lang="php">
        it('returns all', function () {
        $response = $this->postJson('/api/docs', []);

        $response->assertSuccessful();
        });
    </code-snippet>

    ### Mocking
    - Mocking can be very helpful when appropriate.
    - When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
    - You can also create partial mocks using the same import or self method.

    ### Datasets
    - Use datasets in Pest to simplify tests which have a lot of duplicated data. This often the case when testing validation rules, so consider going with this solutionwhen writing tests for validation rules.

    <code-snippet name="Pest Dataset Example" lang="php">
        it('has emails', function (string $email) {
        expect($email)->not->toBeEmpty();
        })->with([
        'james' => 'james@laravel.com',
        'taylor' => 'taylor@laravel.com',
        ]);
    </code-snippet>

    ## Test Enforcement

    - Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
    - Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
