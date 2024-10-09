<script setup>
const props = defineProps({
    onChat: {
        type: Function,
    },
});

const openChat = () => {
    if (props.onChat) {
        props.onChat();
    }
    $crisp.push(['do', 'chat:open'])
};
</script>

<template>
    <p>
        Stuck? <a class="underline" href="javascript:void(0)" @click="openChat()">Open chat
            <img class="inline-block rounded-full not-prose" src="/images/matt-casual-square-32.jpg"
                style="width:1.4em;height:1.4em;vertical-align:-0.3em" /></a>, or <a class="underline"
            href="https://www.x.com/mattjustfyi" target="_blank">DM on X</a>,
        or <a class="underline" href="mailto:support@exnota.com">email</a>. Happy to help :)
    </p>
</template>
