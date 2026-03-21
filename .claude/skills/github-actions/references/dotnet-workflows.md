# .NET / C# Workflow Patterns

## Setup .NET

```yaml
- uses: actions/setup-dotnet@v4
  with:
    dotnet-version: '9.0.x'
```

### Multiple SDK Versions

```yaml
- uses: actions/setup-dotnet@v4
  with:
    dotnet-version: |
      8.0.x
      9.0.x
```

## Build and Test

### Basic CI

```yaml
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-dotnet@v4
        with:
          dotnet-version: '9.0.x'
      - run: dotnet restore
      - run: dotnet build --no-restore --configuration Release
      - run: dotnet test --no-build --configuration Release --verbosity normal
```

### With Code Coverage

```yaml
      - run: dotnet test --no-build --configuration Release --collect:"XPlat Code Coverage"
      - uses: codecov/codecov-action@v4
        with:
          token: ${{ secrets.CODECOV_TOKEN }}
```

## Format and Lint

```yaml
format:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-dotnet@v4
      with:
        dotnet-version: '9.0.x'
    - run: dotnet format --verify-no-changes
```

## Publish and Package

### NuGet Package

```yaml
publish:
  runs-on: ubuntu-latest
  needs: [build]
  if: github.ref == 'refs/heads/main'
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-dotnet@v4
      with:
        dotnet-version: '9.0.x'
    - run: dotnet pack --configuration Release -o ./nupkg
    - run: dotnet nuget push ./nupkg/*.nupkg --api-key ${{ secrets.NUGET_API_KEY }} --source https://api.nuget.org/v3/index.json
```

### Self-Contained Publish

```yaml
    - run: dotnet publish -c Release -r linux-x64 --self-contained -o ./publish
    - uses: actions/upload-artifact@v4
      with:
        name: app-linux-x64
        path: ./publish/
```

### Multi-Platform Build

```yaml
strategy:
  matrix:
    include:
      - { os: ubuntu-latest, rid: linux-x64 }
      - { os: macos-latest, rid: osx-arm64 }
      - { os: windows-latest, rid: win-x64 }
runs-on: ${{ matrix.os }}
steps:
  - uses: actions/checkout@v4
  - uses: actions/setup-dotnet@v4
    with:
      dotnet-version: '9.0.x'
  - run: dotnet publish -c Release -r ${{ matrix.rid }} --self-contained -o ./publish
```

## Docker Build for .NET

```yaml
    - uses: docker/build-push-action@v6
      with:
        context: .
        push: true
        tags: ghcr.io/${{ github.repository }}:latest
        cache-from: type=gha
        cache-to: type=gha,mode=max
```

## Entity Framework Migrations

```yaml
    - run: dotnet tool restore
    - run: dotnet ef database update
      env:
        ConnectionStrings__DefaultConnection: "Host=127.0.0.1;Database=testing;Username=postgres;Password=password"
```

## Matrix Testing

```yaml
strategy:
  fail-fast: true
  matrix:
    dotnet: ['8.0.x', '9.0.x']
    os: [ubuntu-latest, windows-latest]
runs-on: ${{ matrix.os }}
steps:
  - uses: actions/setup-dotnet@v4
    with:
      dotnet-version: ${{ matrix.dotnet }}
  - run: dotnet test --configuration Release
```