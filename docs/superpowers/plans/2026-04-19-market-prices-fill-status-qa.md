# Manual QA Checklist — Market Prices Fill Workflow

Spec: `docs/superpowers/specs/2026-04-19-market-prices-fill-status-design.md`
Plan: `docs/superpowers/plans/2026-04-19-market-prices-fill-status.md`

Run these checks in a dev environment after applying migrations:
- `m260419_000000_add_market_total_price_to_order_items`
- `m260419_000001_add_price_fields_to_order_items_changelog`

## Pre-conditions

- At least one `product_groups` row with `is_market = 1`, linked via
  `product_groups_link` to at least one product.
- A user with `ROLE_ADMIN`, `ROLE_OFFICE`, or `ROLE_OFFICE_MANAGER`.
- A test order that can be progressed through the workflow.

## Checklist

1. Create an order containing at least one bazar product and at least
   one non-bazar product via the normal customer-orders flow.
2. As stock/admin, send and close the order (`orders/close`).
   - Verify `SELECT state FROM orders WHERE id = <id>` returns `4`.
   - Verify Telegram channel `-1001879316029` received the
     «Заказ ожидает заполнения цен базара» message (or at least the
     message text differs from plain «Заказ закрыть»).
3. Repeat with an order that has **no** bazar items.
   - Verify state is `2` and the old «Заказ закрыть» message is sent.
4. Open `/orders/market-prices`.
   - Entry for the state-4 order appears.
   - «Базарных позиций» shows `0 / N` where N is the bazar item count.
5. Click the order id or «Заполнить» button.
   - Fill page shows **only bazar items** (non-bazar items absent).
   - Shop / fact columns show the expected values.
6. Enter some prices, click **«Сохранить»**.
   - Flash `Сохранено позиций: <count>`.
   - Page reloads; values persist (rows refilled from DB).
   - `SELECT market_total_price FROM order_items WHERE orderId = <id>`
     returns the saved values.
   - `SELECT * FROM order_items_changelog WHERE orderId = <id>
     AND action = 'price_updated'` has a row per changed item with
     correct `old_price`, `new_price`, `userId`.
7. Leave one bazar item blank, click **«Сохранить и завершить»**.
   - Flash `Не у всех базарных позиций заполнена сумма: <name>`.
   - Order still `state = 4`.
8. Fill all bazar items, click **«Сохранить и завершить»**.
   - Redirect to `/orders/view?id=<id>`.
   - Order now `state = 2`.
   - CTA «Заполнить цены базара» is gone.
9. Try opening `/orders/market-prices-fill?id=<id>` for a state-2 order.
   - Redirects to `/orders/market-prices` with an error flash.
10. Log in as a user without ADMIN/OFFICE/OFFICE_MANAGER role.
    - Navigating to `/orders/market-prices` returns 403/redirect.
    - Menu entry «Цены базара» is not visible.

## Known limitations

- Automated Codeception Cest was not written in this round because
  `tests/_data/` has no fixtures for orders/products/product_groups.
  When fixtures are added later, implement the Cest from the fixtures-
  present branch of plan Task 13.
