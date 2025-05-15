You are an expert in PHP, Laravel, Pest, Inertia, Vue and Tailwind.

1. Coding Standards
* Use PHP v8.4 features.
* Follow pint.json coding rules.
* Enforce strict types and array shapes via PHPStan.

2. Project Structure & Architecture
* Delete .gitkeep when adding a file.
* Stick to existing structure—no new folders.
* Avoid DB::; use Model::query() only.
* No dependency changes without approval.

2.1 Directory Conventions

app/Http/Controllers
* No abstract/base controllers.

app/Http/Requests
* Use FormRequest for validation.
* Name with Create, Update, Delete.

app/Actions
* Use Actions pattern and naming verbs.
* Use Laravel Actions [package](https://www.laravelactions.com/) for Actions.
* Use `docker compose exec php artisan make:action {actionName}` for Actions creation.
* Use `AsController` trait as default for Actions.

app/DTO
* Use DTO for passing requests data throw Actions

3. Testing
* Use Pest PHP for all tests.
* Cover 100% mutation Pest tests for all tests.
* Run `docker compose exec composer pint:fix` after changes.
* Run `docker compose exec composer rector:fix` after changes.
* Run `docker compose exec app ./vendor/bin/pest --parallel` before finalizing.
* Don’t remove tests without approval.
* All code must be tested.
* Avoid Mocks, use Models Factories instead.
* Generate a {Model}Factory with each model.

3.1 Test Directory Structure
* Console: tests/Feature/Console
* Controllers: tests/Feature/Http
* Actions: tests/Unit/Actions
* Models: tests/Unit/Models
* Jobs: tests/Unit/Jobs
* DTO: tests/Unit/DTO

4. Styling & UI
* Use Tailwind CSS Plus components.
* Use Inertia and VueJS.
* Keep UI minimal.

5. Deploy
* Use Github Actions for deploy.
* Use Docker for build images.

6. Task Completion Requirements
* Recompile assets after frontend changes.
* Follow all rules before marking tasks complete.
