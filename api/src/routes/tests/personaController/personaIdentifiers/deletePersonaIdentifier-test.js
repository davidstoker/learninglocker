import testId from 'api/routes/tests/utils/testId';
import { MongoClient, ObjectID } from 'mongodb';
import mongoModelsRepo from 'personas/dist/mongoModelsRepo';
import config from 'personas/dist/config';
import createPersonaService from 'personas/dist/service';
import setup from 'api/routes/tests/utils/setup';
import * as routes from 'lib/constants/routes';
import createOrgToken from 'api/routes/tests/utils/tokens/createOrgToken';
import { getConnection } from 'lib/connections/mongoose';

describe('deletePersonaIdentifier', () => {
  const apiApp = setup();
  let token;

  let personaService;
  before(async () => {

    const mongoClientPromise = MongoClient.connect(
      process.env.MONGODB_PATH,
      config.mongoModelsRepo.options
    );
    personaService = createPersonaService({
      repo: mongoModelsRepo({
        db: mongoClientPromise
      })
    });
  });

  beforeEach(async () => {
    await personaService.clearService();
    token = await createOrgToken();
  });

  after(async () => {
    await personaService.clearService();
  });

  it('should delete a persona identifier', async () => {
    const organisation = testId;
    const { persona } = await personaService.createPersona({
      organisation,
      name: 'Dave'
    });

    const { identifier } = await personaService.createIdentifier({
      ifi: {
        key: 'mbox',
        value: 'mailto:nostatements@withthisident.com'
      },
      organisation,
      persona: persona.id
    });

    await apiApp.delete(
      routes.PERSONA_IDENTIFIER_ID.replace(':personaIdentifierId', identifier.id)
    ).set('Authorization', `Bearer ${token}`)
      .expect(200);
  });

  it('should not delete a persona identifier when statements exist for the ident', async () => {
    const organisation = testId;
    const { persona } = await personaService.createPersona({
      organisation,
      name: 'Dave'
    });

    const ifi = {
      key: 'mbox',
      value: 'mailto:statementsexist@withthisident.com'
    };

    const { identifier } = await personaService.createIdentifier({
      ifi,
      organisation,
      persona: persona.id
    });

    const connection = getConnection();
    await connection.collection('statements').insert({
      organisation: new ObjectID(organisation),
      statement: {
        actor: { mbox: ifi.value }
      },
      personaIdentifier: new ObjectID(identifier.id),
      persona: new ObjectID(persona.id),
    });

    await apiApp.delete(
      routes.PERSONA_IDENTIFIER_ID.replace(':personaIdentifierId', identifier.id)
    ).set('Authorization', `Bearer ${token}`)
      .expect(400);
  });
});
