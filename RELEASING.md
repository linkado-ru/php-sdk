# Релизы Linkado PHP SDK

Версии `linkado-ru/php-sdk` определяются Git-тегами. Поле `version` в `composer.json` добавлять нельзя.

## Подготовка первого v1.0.0

1. Убедитесь, что `CHANGELOG.md` и `README.md` описывают итоговый публичный контракт.
2. Выполните проверки:

```bash
composer validate --strict
composer test
composer pint
composer test
git diff --check
git status --short
```

3. Проверьте отсутствие secrets, `vendor`, coverage, cache и editor artifacts в tracked files.
4. Зафиксируйте изменения и отправьте основную ветку в настроенный GitHub remote:

```bash
git add .
git commit -m "feat: release Linkado PHP SDK v1"
git push origin main
```

5. После проверки commit создайте и отправьте annotated tag:

```bash
git tag -a v1.0.0 -m "v1.0.0"
git push origin v1.0.0
```

6. Отправьте URL GitHub-репозитория в Packagist как `linkado-ru/php-sdk` и включите auto-update. Если webhook задержался, запустите обновление со страницы пакета.

## SemVer

- Patch: совместимые исправления, документация и внутреннее усиление.
- Minor: новые совместимые endpoint, resource, method, DTO или optional field.
- Major: несовместимое изменение namespace, requirements, method, поведения или публичного constructor.

Не изменяйте опубликованные теги. Ошибка в релизе исправляется новым patch-релизом. Push, tag и Packagist publication выполняются только по явному запросу владельца.

