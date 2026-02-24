const TOKEN_KEY = 'betaki_admin_token';
const EXPIRES_KEY = 'betaki_admin_expires_at';

Cypress.Commands.add('login', () => {
  cy.window().then((win) => {
    win.localStorage.setItem(TOKEN_KEY, 'fake-cypress-token');
    win.localStorage.setItem(EXPIRES_KEY, '2099-12-31T23:59:59Z');
  });
});

Cypress.Commands.add('loginViaUI', (email = 'admin@test.com', password = 'password') => {
  cy.visit('/login');
  cy.get('#email').type(email);
  cy.get('#password').type(password);
  cy.get('#loginForm').submit();
});

Cypress.Commands.add('setupAuthIntercepts', () => {
  cy.intercept('GET', '/api/v1/auth/me', { fixture: 'auth/me.json' }).as('getMe');
  cy.intercept('POST', '/api/v1/auth/logout', { statusCode: 204, body: {} }).as('logout');
});

Cypress.Commands.add('visitAuthenticated', (url) => {
  cy.login();
  cy.setupAuthIntercepts();
  cy.visit(url);
  cy.wait('@getMe');
});
