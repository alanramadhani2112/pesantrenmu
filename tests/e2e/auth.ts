export const authStates = {
  admin: 'tests/e2e/.auth/admin.json',
  asesor: 'tests/e2e/.auth/asesor.json',
  pesantren: 'tests/e2e/.auth/pesantren.json',
} as const;

export const authUsers = {
  admin: { email: 'bf.admin@test.local', password: 'password', state: authStates.admin },
  asesor: { email: 'bf.asesor1@test.local', password: 'password', state: authStates.asesor },
  pesantren: { email: 'bf.pesantren@test.local', password: 'password', state: authStates.pesantren },
} as const;
