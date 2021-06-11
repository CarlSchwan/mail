import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert'

export async function deleteThread(id) {
	const url = generateUrl('/apps/mail/api/thread/{id}', {
		id,
	})

	try {
		return await axios.delete(url)
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function moveThread(id, destMailboxId) {
	const url = generateUrl('/apps/mail/api/thread/{id}', {
		id,
	})

	try {
		return await axios.post(url, { destMailboxId })
	} catch (e) {
		throw convertAxiosError(e)
	}
}
