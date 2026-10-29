import {
    Callout,
    Field,
    Fields,
    Guide,
    GuideSection,
    Message,
    Para,
    RelatedGuide,
    Shot,
    Step,
    Steps,
    Ui,
} from '@/components/training/vocabulary';

/**
 * Settings → Security (`security.edit`) — changing your own password.
 * Source: documentation/settings.md §2, ARCHITECTURE.md §9.2.
 */
export default function SecurityGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Change your own password. When an administrator has just created your account or set a new password for you, this is the only screen you can open until you choose your own."
                    bn="নিজের পাসওয়ার্ড বদলানো। কোনো অ্যাডমিনিস্ট্রেটর সবে আপনার অ্যাকাউন্ট খুলে দিলে বা আপনার জন্য নতুন পাসওয়ার্ড দিলে, নিজের পাসওয়ার্ড বেছে না নেওয়া পর্যন্ত আপনি শুধু এই স্ক্রিনটিই খুলতে পারবেন।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en="Everyone who can sign in. No permission is needed."
                    bn="যাঁরা সাইন ইন করতে পারেন, তাঁদের সবাই। কোনো পারমিশন লাগে না।"
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en={
                        <>
                            You need your current password. If you have
                            forgotten it, use <Ui>Forgot your password?</Ui> on
                            the sign-in page, or ask an administrator to set a
                            new one for you.
                        </>
                    }
                    bn={
                        <>
                            আপনার বর্তমান পাসওয়ার্ডটি লাগবে। সেটি ভুলে গেলে
                            সাইন-ইন পাতার <Ui>Forgot your password?</Ui> ব্যবহার
                            করুন, অথবা একজন অ্যাডমিনিস্ট্রেটরকে নতুন একটি দিয়ে
                            দিতে বলুন।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en={
                            <>
                                In Settings, choose <Ui>Security</Ui>. If you
                                have not typed your password for a while, you
                                are asked to confirm it first.
                            </>
                        }
                        bn={
                            <>
                                Settings-এ <Ui>Security</Ui> বেছে নিন। কিছুক্ষণ
                                ধরে পাসওয়ার্ড না লিখে থাকলে আগে সেটি নিশ্চিত
                                করতে বলা হয়।
                            </>
                        }
                    >
                        <Shot
                            id="overview"
                            en="Update password: current password, new password with its checklist, and the confirmation."
                            bn="Update password: বর্তমান পাসওয়ার্ড, চেকলিস্টসহ নতুন পাসওয়ার্ড, আর নিশ্চিতকরণ।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Type your current password, then the new one
                                twice. Press <Ui>Save</Ui> once every line of
                                the checklist is ticked.
                            </>
                        }
                        bn={
                            <>
                                বর্তমান পাসওয়ার্ড লিখুন, তারপর নতুনটি দুবার।
                                চেকলিস্টের প্রতিটি শর্ত টিক হয়ে গেলে{' '}
                                <Ui>Save</Ui> চাপুন।
                            </>
                        }
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="New password"
                        en="Must meet every rule in the checklist under it; each line ticks as you type. A password known from public data breaches is refused too."
                        bn="নিচের চেকলিস্টের প্রতিটি নিয়ম পূরণ করতে হবে; টাইপ করার সাথে সাথে প্রতিটি শর্ত টিক হয়ে যায়। প্রকাশ্য ডেটা ফাঁসে পাওয়া কোনো পাসওয়ার্ডও গ্রহণ করা হয় না।"
                    />
                </Fields>
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text="Choose your own password. Your password was set by someone else. Enter it as your current password and choose a new one to continue using the application."
                    en="Shown at the top while your password is a temporary one. Type the password you were given as Current password, choose a new one and save; every other screen opens again once it is saved."
                    bn="পাসওয়ার্ড অস্থায়ী থাকা অবস্থায় উপরে দেখায়। যে পাসওয়ার্ডটি আপনাকে দেওয়া হয়েছিল সেটি Current password-এ লিখুন, নতুন একটি বেছে নিয়ে সেভ করুন; সেভ হলেই অন্য সব স্ক্রিন আবার খোলে।"
                />
                <Message
                    text="Password updated."
                    en="Saved. Use the new password from your next sign-in."
                    bn="সেভ হয়েছে। পরের সাইন-ইন থেকে নতুন পাসওয়ার্ডটি ব্যবহার করুন।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="After six tries in a minute the screen makes you wait before trying again — a wrong current password counts."
                    bn="এক মিনিটে ছয়বার চেষ্টার পর আবার চেষ্টা করার আগে স্ক্রিন আপনাকে অপেক্ষা করায় — ভুল বর্তমান পাসওয়ার্ডও এর মধ্যে গোনা হয়।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="profile.edit"
                    en="Your name, photo, username and email."
                    bn="আপনার নাম, ছবি, ইউজারনেম ও ইমেইল।"
                />
            </GuideSection>
        </Guide>
    );
}
