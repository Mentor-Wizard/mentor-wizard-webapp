You are an expert in PHP, Laravel, Pest, Inertia and Tailwind.

1. Coding Standards
   •	Use PHP v8.4 features.
   •	Follow pint.json coding rules.
   •	Enforce strict types and array shapes via PHPStan.

2. Project Structure & Architecture
   •	Delete .gitkeep when adding a file.
   •	Stick to existing structure—no new folders.
   •	Avoid DB::; use Model::query() only.
   •	No dependency changes without approval.

2.1 Directory Conventions

app/Http/Controllers
•	No abstract/base controllers.

app/Http/Requests
•	Use FormRequest for validation.
•	Name with Create, Update, Delete.

app/Actions
•	Use Actions pattern and naming verbs.
•	Use Laravel Actions [package](https://www.laravelactions.com/) for Actions.
•	Use `AsController` trait as default for Actions.
•	Example:

```php
public function handle(CreateTodoRequest $request)
{
    $user = $request->user();

    $action->handle($user, $request->validated());
}
```

[//]: # (app/Models)

[//]: # (•	Avoid fillable.)

[//]: # ()
[//]: # (database/migrations)

[//]: # (•	Omit down&#40;&#41; in new migrations.)

3. Testing
   •	Use Pest PHP for all tests.
   •	Cover 100% mutation tests for all tests.
   •	Run composer pint:fix after changes.
   •	Run composer rector:fix after changes.
   •	Run `dce app ./vendor/bin/pest --parallel` before finalizing.
   •	Don’t remove tests without approval.
   •	All code must be tested.
   •	Generate a {Model}Factory with each model.

3.1 Test Directory Structure
•	Console: tests/Feature/Console
•	Controllers: tests/Feature/Http
•	Actions: tests/Unit/Actions
•	Models: tests/Unit/Models
•	Jobs: tests/Unit/Jobs

4. Styling & UI
   •	Use Tailwind CSS Plus components.
   •	Use Ineria and VueJS.
   •	Keep UI minimal.

5. Task Completion Requirements
   •	Recompile assets after frontend changes.
   •	Follow all rules before marking tasks complete.
