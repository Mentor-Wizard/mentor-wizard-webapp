import { computed, ref } from 'vue';

const listUsers = ref([]);
const messageSortList = ['Resent', 'New', 'Name'];
const messageSortBy = ref(1);
let channel = null;

export function useCaseChat() {
  const fetchUsers = async () => {
    const { data } = await axios.get(route('chat.users'));
    listUsers.value = data.users;
    subscribeUser();
    console.log(data.users);
  };

  const subscribeUser = () => {
    console.log('subscribeUser');

    const updateOnlineStatus = (userId, isOnline) => {
      const userIndex = listUsers.value.findIndex((u) => u.id === userId);

      if (userIndex !== -1) {
        listUsers.value[userIndex].online = isOnline;
      }
    };

    channel = Echo.join('presence-online-users')
      .here((onlineUsersList) => {
        console.log('here. list users:', onlineUsersList);

        const onlineIds = new Set(onlineUsersList.map((u) => u.id));

        listUsers.value.forEach((user) => {
          console.log(onlineIds.has(user.id));
          user.online = onlineIds.has(user.id);
        });
      })
      .joining((user) => {
        console.log('joining. user:', user);
        updateOnlineStatus(user.id, true);
      })
      .leaving((user) => {
        console.log('leaving. Користувач відключився:', user);
        updateOnlineStatus(user.id, false);
      })
      .error((error) => {
        console.error('Error Presence Channel:', error);
      });
  };

  const unsubscribeUser = () => {
    if (channel) {
      channel.leave();
    }
  };

  const sortedUsers = computed(() => {
    const users = [...listUsers.value];

    const sortIndex = messageSortBy.value;

    switch (sortIndex) {
      case 0: // 'Resent'
        return users.sort((a, b) => {
          // Використовуємо .getTime() для порівняння об'єктів Date
          const dateA = new Date(a['created_at']).getTime();
          const dateB = new Date(b['created_at']).getTime();
          // Сортування від більшого до меншого (новіші перші)
          return dateB - dateA;
        });

      case 1: // 'New'
        return users.sort((a, b) => {
          const dateA = new Date(a['created_at']).getTime();
          const dateB = new Date(b['created_at']).getTime();
          // Сортування від меншого до більшого (старіші перші)
          return dateA - dateB;
        });

      case 2: // 'Name'
        return users.sort((a, b) => {
          const nameA = a.name.toLowerCase();
          const nameB = b.name.toLowerCase();

          if (nameA < nameB) return -1;
          if (nameA > nameB) return 1;
          return 0;
        });

      default:
        return users;
    }
  });

  return {
    subscribeUser,
    unsubscribeUser,
    fetchUsers,
    sortedUsers,
    messageSortList,
    messageSortBy,
  };
}
