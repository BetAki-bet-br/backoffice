export default class LoginPage {
  visit() {
    cy.visit('/login');
    return this;
  }

  getEmailInput() {
    return cy.get('#email');
  }

  getPasswordInput() {
    return cy.get('#password');
  }

  getRememberCheckbox() {
    return cy.get('#remember');
  }

  getSubmitButton() {
    return cy.get('#loginBtn');
  }

  getAlert() {
    return cy.get('#loginAlert');
  }

  fillEmail(email) {
    this.getEmailInput().clear().type(email);
    return this;
  }

  fillPassword(password) {
    this.getPasswordInput().clear().type(password);
    return this;
  }

  checkRemember() {
    this.getRememberCheckbox().check();
    return this;
  }

  uncheckRemember() {
    this.getRememberCheckbox().uncheck();
    return this;
  }

  submit() {
    cy.get('#loginForm').submit();
    return this;
  }

  login(email, password) {
    this.fillEmail(email);
    this.fillPassword(password);
    this.submit();
    return this;
  }
}
